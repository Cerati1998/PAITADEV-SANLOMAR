<?php

namespace App\Livewire;

use App\Livewire\Forms\EmailForm;
use App\Livewire\Forms\VoidedForm;
use App\Livewire\Forms\WhatsappForm;
use App\Mail\DocumentSent;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Invoice;
use App\Services\Sunat\UtilService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class VoucherTable extends DataTableComponent
{
    public $openVoidedModal = false;
    public VoidedForm $voided;

    public WhatsappForm $whatsapp;
    public EmailForm $email;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects(['id', 'tipoDoc', 'serie', 'correlativo', 'tipoMoneda', 'voided']);
        $this->setDefaultSort('id', 'desc');

        $this->setConfigurableAreas([
            'after-wrapper' => 'vouchers.partials.modals',
        ]);

        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if ($row->voided) {
                return [
                    'class' => 'line-through text-red-500',
                ];
            }

            return [];
        });
    }

    public function columns(): array
    {
        return [
            Column::make("Fecha", "fechaEmision")
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('Comprobante')
                ->label(
                    function ($row) {
                        $tipoDoc = match ($row->tipoDoc) {
                            '01' => 'Factura',
                            '03' => 'Boleta',
                            '07' => 'Nota de crédito',
                            '08' => 'Nota de débito',
                            '09' => 'Guía de remisión',
                            default => 'Otro',
                        };

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

            Column::make("Cliente", "client")
                ->format(
                    function ($value) {

                        $numDoc = $value['numDoc'] ?? 'S/D';
                        $rznSocial = Str::limit($value['rznSocial'] ?? 'S/D', 30);

                        return $numDoc . '<br>' . $rznSocial;
                    }
                )
                //Buscar por numDoc o rznSocial en el campo de tipo json
                ->searchable(function ($query, $search) {
                    $query->orWhere('client->numDoc', 'like', '%' . $search . '%')
                        ->orWhereRaw("LOWER(json_unquote(json_extract(client, '$.rznSocial'))) LIKE ?", ['%' . strtolower($search) . '%']);
                })
                ->html(),

            Column::make("Monto", "mtoImpVenta")
                ->format(function ($value, $row) {
                    return $row->tipoMoneda . ' ' . number_format($value, 2);
                })
                ->sortable(),

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
                    fn($row) => view('vouchers.partials.action', compact('row'))
                )->collapseOnTablet()
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Documentos')
                ->options([
                    '' => 'Todos',
                    '01' => 'Factura',
                    '03' => 'Boleta',
                    '07' => 'Nota de Crédito',
                    '08' => 'Nota de Débito',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('tipoDoc', $value);
                }),

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

    //Métodos
    public function downloadPDF(Invoice $invoice)
    {
        return Storage::download($invoice->pdf_path);
    }

    public function downloadXML(Invoice $invoice)
    {
        return Storage::download($invoice->xml_path);
    }

    public function sendXml(Invoice $invoice)
    {
        $directory = $invoice->production ? 'sunat/' : 'sunat/beta/';
        /* $name = session('company')->ruc."-{$invoice->tipoDoc}-". $invoice->serie.'-'. $invoice->correlativo; */

        try {

            $util = new UtilService(session('company'));
            $see = $util->getSee();
            $result = $see->sendXmlFile($invoice->xml);

            $invoice->sunatResponse = $util->getResponse($result);

            // Guardamos el CDR
            if ($result->getCdrZip()) {
                /* $invoice->cdr_path = $directory . 'cdr/R-' . $name . '.zip'; */
                $invoice->cdr_path = $directory . 'cdr/R-' . Str::uuid() . '.zip';
                Storage::put($invoice->cdr_path, $result->getCdrZip());
            }

            $invoice->save();

            $this->showResponse($invoice);
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage()
            ]);
        }
    }

    public function downloadCDR(Invoice $invoice)
    {
        return Storage::download($invoice->cdr_path);
    }

    public function showResponse(Invoice $invoice)
    {
        $title = 'Detalle de la ';
        $title .= match ($invoice->tipoDoc) {
            '01' => 'factura',
            '03' => 'boleta',
            '07' => 'nota de crédito',
            '08' => 'nota de débito',
            default => 'Otro',
        };

        $html = view('vouchers.partials.sunatResponse', [
            'document' => $invoice
        ])->render();

        $this->dispatch('swal', [
            'icon' => $invoice->sunatResponse['success'] ? 'info' : 'error',
            'title' => $title,
            'html' => $html
        ]);
    }

    //Enviar comprobante whatsapp
    public function openModalWhatsapp(Invoice $invoice)
    {
        $this->whatsapp->openModal = true;
        $this->whatsapp->document = $invoice;
        $this->whatsapp->client = $invoice->client;
    }

    public function sendWhatsapp($type)
    {

        $this->whatsapp->validate();

        $phone = $this->whatsapp->phone_code . $this->whatsapp->phone_number;
        $pdf_url = Storage::url($this->whatsapp->document->pdf_path);
        $mensaje = "Estimado%20cliente%2C%0ASe%20envía%20la%20FACTURA%20ELECTRÓNICA%20{$this->whatsapp->document->serie}-{$this->whatsapp->document->correlativo}.%20Para%20ver%20click%20en%20el%20siguiente%20enlace%3A%0A%0A{$pdf_url}";

        if ($type == 'web') {
            $url = "https://web.whatsapp.com/send?phone={$phone}&text={$mensaje}";
        } else {
            $url = "https://api.whatsapp.com/send?phone={$phone}&text={$mensaje}";
        }

        $this->whatsapp->openModal = false;

        $this->dispatch('redirect', $url);
    }

    public function openModalEmail(Invoice $invoice)
    {
        $this->email->openModal = true;
        $this->email->document = $invoice;
        $this->email->client = $invoice->client;
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

    //Anular comprobante
    public function voidReason(Invoice $invoice)
    {

        if (empty($invoice->sunatResponse && $invoice->sunatResponse['success'] && $invoice->sunatResponse['cdrResponse']['code'] == '0')) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Comprobante no enviado a Sunat',
                'text' => 'El comprobante no ha sido enviado a Sunat o no ha sido aceptado.'
            ]);

            return;
        }

        if ($invoice->fechaEmision->diffInDays(now()) > 7) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Comprobante no puede ser dado de baja',
                'text' => 'Solo se puede dar de baja comprobantes emitidos con 7 días de anticipación.'
            ]);

            return;
        }

        if ($invoice->voided) {
            $this->dispatch('swal', [
                'icon' => 'warning',
                'title' => 'Comprobante ya dado de baja',
                'text' => 'El comprobante ya fue dado de baja anteriormente.'
            ]);

            return;
        }

        $this->openVoidedModal = true;

        $this->voided->fecGeneracion = now();
        $this->voided->fecComunicacion = now();

        $this->voided->details = [
            [
                'tipoDoc' => $invoice->tipoDoc,
                'serie' => $invoice->serie,
                'correlativo' => $invoice->correlativo,
                'desMotivoBaja' => '',
            ]
        ];

        $this->voided->branch_id = session('branch')->id;
        $this->voided->company_id = session('company')->id;
        $this->voided->production = session('company')->production;
    }

    public function sendVoided()
    {
        $this->voided->send();
        $this->openVoidedModal = false;
    }

    //Consulta
    public function builder(): Builder
    {
        return Invoice::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id)
            ->where('production', session('company')->production);
    }
}
