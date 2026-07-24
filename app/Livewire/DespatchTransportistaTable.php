<?php

namespace App\Livewire;

use App\Livewire\Forms\EmailForm;
use App\Livewire\Forms\WhatsappForm;
use App\Mail\DocumentSent;
use App\Models\DespatchTransportista;
use App\Services\Sunat\Transportista\DocumentTransportistaService;
use App\Services\Sunat\Transportista\UtilTransportistaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class DespatchTransportistaTable extends DataTableComponent
{
    public WhatsappForm $whatsapp;
    public EmailForm $email;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects(['id', 'serie', 'correlativo']);

        $this->setConfigurableAreas([
            'after-wrapper' => 'despatch-transportistas.partials.modals',
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
                        return 'Guía de remisión transportista: ' . $row->serie . '-' . $row->correlativo;
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

            Column::make("Remitente", "remitente")
                ->format(
                    function ($value) {
                        $numDoc = $value['numDoc'] ?? 'S/D';
                        $rznSocial = Str::limit($value['rznSocial'] ?? 'S/D', 30);
                        return $numDoc . '<br>' . $rznSocial;
                    }
                )
                ->searchable(function ($query, $search) {
                    $query->orWhere('remitente->numDoc', 'like', '%' . $search . '%')
                        ->orWhereRaw("LOWER(json_unquote(json_extract(remitente, '$.rznSocial'))) LIKE ?", ['%' . strtolower($search) . '%']);
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
                    fn($value, $row) => view('vouchers.partials.cdr', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('Sunat', 'sunatResponse')
                ->format(
                    fn($value, $row) => view('vouchers.partials.response', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('Opciones')
                ->label(
                    fn($row) => view('despatch-transportistas.partials.action', compact('row'))
                )->collapseOnTablet()
        ];
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Fecha')
                ->config([
                    'altFormat' => 'F j, Y',
                    'ariaDateFormat' => 'F j, Y',
                    'dateFormat' => 'Y-m-d',
                    'placeholder' => 'Introduzca el rango de fechas',
                    'locale' => 'en',
                ])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder
                        ->whereDate('fechaEmision', '>=', $dateRange['minDate'])
                        ->whereDate('fechaEmision', '<=', $dateRange['maxDate']);
                }),
        ];
    }

    public function builder(): Builder
    {
        return DespatchTransportista::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id)
            ->where('production', session('company')->production)
            ->orderBy('id', 'desc');
    }

    //Métodos
    public function downloadPDF(DespatchTransportista $despatch)
    {
        return Storage::download($despatch->pdf_path);
    }

    public function downloadXML(DespatchTransportista $despatch)
    {
        return Storage::download($despatch->xml_path);
    }

    public function sendXml(DespatchTransportista $despatch)
    {
        $directory = $despatch->production ? 'sunat/' : 'sunat/beta/';
        $name = session('company')->ruc . "-{$despatch->tipoDoc}-{$despatch->serie}-{$despatch->correlativo}";

        try {
            $util = new UtilTransportistaService(session('company'));

            // Si no tiene ticket ni XML (fallo al generar la primera vez), lo regeneramos.
            if (!$despatch->sunat_ticket && empty($despatch->xml_path)) {
                $document = new DocumentTransportistaService();
                $despatchGreenter = $document->getDespatch($despatch);

                $regeneratedXml = $util->getXmlSigned($despatchGreenter);
                $despatch->hash = $util->getHashSign($regeneratedXml);
                $despatch->xml_path = $directory . 'xml/' . $despatchGreenter->getName() . '.xml';
                Storage::put($despatch->xml_path, $regeneratedXml);

                $pdf = $util->getReportPdf($despatchGreenter, $despatch->hash);
                $despatch->pdf_path = $directory . 'cpe/' . $despatchGreenter->getName() . '.pdf';
                Storage::put($despatch->pdf_path, $pdf);

                $despatch->save();
            }

            $api = $util->getSeeApi();

            if ($despatch->sunat_ticket) {
                $result = $api->getStatus($despatch->sunat_ticket);
            } else {
                $xml = Storage::get($despatch->xml_path);
                $result = $api->sendXml($name, $xml);

                if (!$result->isSuccess()) {
                    $despatch->sunatResponse = $util->getErrorResponse($result);
                    $despatch->save();
                    $this->showResponse($despatch);
                    return;
                }

                if (!($result instanceof \Greenter\Model\Response\SummaryResult)) {
                    $this->dispatch('swal', [
                        'icon' => 'error',
                        'title' => 'Error inesperado',
                        'text' => 'SUNAT no devolvió un ticket para esta guía.',
                    ]);
                    return;
                }

                $despatch->sunat_ticket = $result->getTicket();
                $despatch->save();

                $result = $api->getStatus($despatch->sunat_ticket);
            }

            if (!$result->isSuccess()) {
                $code = $result->getError()?->getCode();

                if ($code === '98') {
                    $this->dispatch('swal', [
                        'icon' => 'info',
                        'title' => 'Aún en proceso',
                        'text' => 'SUNAT todavía no termina. Reintenta en unos segundos.',
                    ]);
                    return;
                }

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();
                $this->showResponse($despatch);
                return;
            }

            $despatch->cdr_path = $directory . 'cdr/R-' . $name . '.zip';
            Storage::put($despatch->cdr_path, $result->getCdrZip());

            $despatch->sunatResponse = $util->readCdr($result->getCdrResponse());
            $despatch->sunat_ticket = null;
            $despatch->save();

            $this->showResponse($despatch);

        } catch (\Throwable $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage(),
            ]);
        }
    }

    public function downloadCDR(DespatchTransportista $despatch)
    {
        return Storage::download($despatch->cdr_path);
    }

    public function showResponse(DespatchTransportista $despatch)
    {
        $html = view('vouchers.partials.sunatResponse', [
            'document' => $despatch
        ])->render();

        $this->dispatch('swal', [
            'icon' => $despatch->sunatResponse['success'] ? 'info' : 'error',
            'title' => 'Detalle de la guía de remisión transportista',
            'html' => $html
        ]);
    }

    public function openModalWhatsapp(DespatchTransportista $despatch)
    {
        $this->whatsapp->openModal = true;
        $this->whatsapp->document = $despatch;
        $this->whatsapp->client = $despatch->remitente;
    }

    public function sendWhatsapp($type)
    {
        $this->whatsapp->validate();

        $phone = $this->whatsapp->phone_code . $this->whatsapp->phone_number;
        $pdf_url = Storage::url($this->whatsapp->document->pdf_path);
        $mensaje = "Estimado%20cliente%2C%0ASe%20envía%20la%20GUÍA%20ELECTRÓNICA%20{$this->whatsapp->document->serie}-{$this->whatsapp->document->correlativo}.%20Para%20ver%20click%20en%20el%20siguiente%20enlace%3A%0A%0A{$pdf_url}";

        if ($type == 'web') {
            $url = "https://web.whatsapp.com/send?phone={$phone}&text={$mensaje}";
        } else {
            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$mensaje}";
        }

        $this->whatsapp->openModal = false;

        $this->dispatch('redirect', $url);
    }

    public function openModalEmail(DespatchTransportista $despatch)
    {
        $this->email->openModal = true;
        $this->email->document = $despatch;
        $this->email->client = $despatch->remitente;
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
