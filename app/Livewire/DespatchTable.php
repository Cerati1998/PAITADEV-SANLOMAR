<?php

namespace App\Livewire;

use App\Livewire\Forms\EmailForm;
use App\Livewire\Forms\WhatsappForm;
use App\Mail\DocumentSent;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Despatch;
use App\Services\Sunat\DespatchService;
use App\Services\Sunat\UtilService;
use Greenter\Report\XmlUtils;
use Greenter\Xml\Builder\DespatchBuilder;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class DespatchTable extends DataTableComponent
{

    public WhatsappForm $whatsapp;
    public EmailForm $email;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects(['id', 'serie', 'correlativo']);

        $this->setConfigurableAreas([
            'after-wrapper' => 'despatchs.partials.modals',
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("F. Emisión", "fechaEmision")
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('Comprobante')
                ->label(
                    function ($row) {
                        $tipoDoc = 'Guía de remisión';
                        return $tipoDoc . ': ' . $row->serie . '-' . $row->correlativo;
                    }
                )
                ->searchable(function ($query, $search) {

                    $search = explode('-', $search);

                    if (count($search) === 1) {
                        $query->orWhere('serie', 'like', '%' . $search[0] . '%')
                            ->orWhere('correlativo', 'like', '%' . $search[0] . '%');

                        return;
                    }

                    $query->orWhere(function ($query) use ($search) {
                        $query->where('serie', 'like', '%' . $search[0] . '%')
                            ->where('correlativo', 'like', '%' . $search[1] . '%');
                    });
                }),

            Column::make("Cliente", "destinatario")
                ->format(
                    function ($value) {

                        $numDoc = $value['numDoc'] ?? 'S/D';
                        $rznSocial = Str::limit($value['rznSocial'] ?? 'S/D', 30);

                        return $numDoc . '<br>' . $rznSocial;
                    }
                )
                ->searchable(function ($query, $search) {
                    $query->orWhere('destinatario->numDoc', 'like', '%' . $search . '%')
                        ->orWhereRaw("LOWER(json_unquote(json_extract(destinatario, '$.rznSocial'))) LIKE ?", ['%' . strtolower($search) . '%']);
                        
                })
                ->html(),

            Column::make('PDF', 'pdf_path')
                ->format(
                    fn($value, $row) => view('vouchers.partials.pdf', compact('row'))
                )->collapseOnTablet(),

            Column::make('XML', 'xml_path')
                ->format(
                    fn($value, $row) => view('vouchers.partials.xml', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('CDR', 'cdr_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.cdr', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('Sunat', 'sunatResponse')
                ->format(
                    fn($value, $row) => view('vouchers.partials.response', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('Opciones')
                ->label(
                    fn ($row) => view('despatchs.partials.action', compact('row'))
                )->collapseOnTablet()
           
        ];
    }

    public function filters(): array
    {
        return [
            //Filtro por fecha
            DateRangeFilter::make('Fecha')
                ->config([
                    'altFormat' => 'F j, Y', // Date format that will be displayed once selected
                    'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
                    'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
                    'placeholder' => 'Introduzca el rango de fechas', // A placeholder value
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
                    $builder
                        ->whereDate('fechaEmision', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('fechaEmision', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),
        ];
    }

    public function builder(): Builder
    {
        return Despatch::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id)
            ->where('production', session('company')->production)
            ->orderBy('id', 'desc');
    }

    //Métodos
    public function downloadPDF(Despatch $despatch)
    {   
        return Storage::download($despatch->pdf_path);   
    }

    public function downloadXML(Despatch $despatch)
    {
        return Storage::download($despatch->xml_path);
    }

    public function sendXml(Despatch $despatch)
    {
        $directory = $despatch->production ? 'sunat/' : 'sunat/beta/';
        $name = session('company')->ruc."-{$despatch->tipoDoc}-". $despatch->serie.'-'. $despatch->correlativo;

        try {
            $util = new UtilService(session('company'));
            $api = $util->getSeeApi();
            $result = $api->sendXml($name, $despatch->xml);

            if (!$result->isSuccess()) {

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();

                $this->showResponse($despatch);

                return;
            }

            $ticket = $result->getTicket();
            $result = $api->getStatus($ticket);

            if (!$result->isSuccess()) {

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();

                $this->showResponse($despatch);

                return;
            }

            // Guardamos el CDR
            $despatch->cdr_path = $directory . 'cdr/R-' . $name . '.zip';
            Storage::put($despatch->cdr_path, $result->getCdrZip());

            $cdr = $result->getCdrResponse();
            $despatch->sunatResponse = $util->readCdr($cdr);
            $despatch->save();

            $this->showResponse($despatch);

        } catch (\Exception $e) {

            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage()
            ]);

        }
        
    }

    public function downloadCDR(Despatch $despatch)
    {
        return Storage::download($despatch->cdr_path);
    }

    public function showResponse(Despatch $despatch)
    {
        $html = view('vouchers.partials.sunatResponse', [
            'document' => $despatch
        ])->render();

        $this->dispatch('swal', [
            'icon' => $despatch->sunatResponse['success'] ? 'info' : 'error',
            'title' => 'Detalle de la guía de remisión',
            'html' => $html
        ]);
    }

    public function openModalWhatsapp(Despatch $despatch)
    {
        $this->whatsapp->openModal = true;
        $this->whatsapp->document = $despatch;
        $this->whatsapp->client = $despatch->destinatario;
    }

    public function sendWhatsapp($type)
    {

        $this->whatsapp->validate();

        $phone = $this->whatsapp->phone_code . $this->whatsapp->phone_number;
        $pdf_url = Storage::url($this->whatsapp->document->pdf_path);
        $mensaje = "Estimado%20cliente%2C%0ASe%20envía%20la%20GUÍA%20ELECTRÓNICA%20{$this->whatsapp->document->serie}-{$this->whatsapp->document->correlativo}.%20Para%20ver%20click%20en%20el%20siguiente%20enlace%3A%0A%0A{$pdf_url}";

        if ($type == 'web') {
            $url = "https://web.whatsapp.com/send?phone={$phone}&text={$mensaje}";
        }else{
            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$mensaje}";
        }

        $this->whatsapp->openModal = false;

        $this->dispatch('redirect', $url);
    }

    public function openModalEmail(Despatch $despatch)
    {
        $this->email->openModal = true;
        $this->email->document = $despatch;
        $this->email->client = $despatch->destinatario;
    }

    public function sendEmail()
    {
        $this->email->validate();

        Mail::to($this->email->value)
            ->send(new DocumentSent($this->email->document, $this->email->client));

        $this->email->openModal = false;

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Correo enviado',
            'text' => 'El correo ha sido enviado correctamente.'
        ]);
    }
    
}
