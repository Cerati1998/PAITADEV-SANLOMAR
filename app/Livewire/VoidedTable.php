<?php

namespace App\Livewire;

use App\Enums\VoidedStatus;
use App\Models\Voided;
use App\Services\Sunat\UtilService;
use App\Services\Sunat\VoidedService;
use App\Services\Sunat\VoidedTicketFlowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class VoidedTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects(['id', 'sunat_ticket', 'sunatResponse']);
        $this->setDefaultSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha Generacion', 'fecGeneracion')
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('Fecha Comunicación', 'fecComunicacion')
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('correlativo')
                ->format(
                    function ($value) {
                        return 'Comunicación de baja: '.$value;
                    }
                ),

            Column::make('PDF', 'pdf_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.pdf', compact('row'))
                )->collapseOnTablet(),

            Column::make('XML', 'xml_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.xml', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('CDR', 'cdr_path')
                ->format(
                    fn ($value, $row) => view('vouchers.partials.cdr', compact('value', 'row'))
                )->collapseOnTablet(),

            Column::make('Sunat', 'sunat_status')
                ->format(
                    fn ($value, $row) => view('voideds.partials.response', compact('row'))
                )->collapseOnTablet(),
        ];
    }

    //Métodos
    public function downloadPDF(Voided $voided)
    {
        return Storage::download($voided->pdf_path);
    }

    public function downloadXML(Voided $voided)
    {
        return Storage::download($voided->xml_path);
    }

    public function sendXml(Voided $voided)
    {
        // D-09: guarda de reenvío, primero de todo. Las acciones de Livewire
        // son invocables directamente desde el cliente, así que ocultar el
        // botón en la vista no basta: si SUNAT ya recibió esta comunicación
        // de baja (hay ticket), reenviar el XML devuelve 2109 y el CDR nunca
        // se recupera.
        if (! is_null($voided->sunat_ticket)) {
            $this->dispatch('swal', [
                'icon' => 'info',
                'title' => 'En proceso',
                'text' => 'SUNAT ya recibió esta comunicación de baja y su ticket quedó guardado.',
            ]);

            return;
        }

        $directory = $voided->production ? 'sunat/' : 'sunat/beta/';
        $name = session('company')->ruc.'-RC-'.$voided->fecGeneracion->format('Ymd').'-'.$voided->correlativo;

        $util = new UtilService(session('company'));
        $flow = new VoidedTicketFlowService($util);

        //Enviar a SUNAT
        try {

            // Si no tiene XML (fallo al generar la primera vez), lo regeneramos.
            if (empty($voided->xml_path)) {
                $voidedGreenter = (new VoidedService)->getVoided($voided);

                $xml = $util->getXmlSigned($voidedGreenter);
                $voided->hash = $util->getHashSign($xml);
                $voided->xml_path = $directory.'xml/'.$voidedGreenter->getName().'.xml';
                Storage::put($voided->xml_path, $xml);

                $pdf = $util->getReportPdf($voidedGreenter, $voided->hash);
                $voided->pdf_path = $directory.'cpe/'.$voidedGreenter->getName().'.pdf';
                Storage::put($voided->pdf_path, $pdf);

                $voided->save();
            } else {
                $xml = $voided->xml;
            }

            $cdrPath = $directory.'cdr/R-'.$name.'.zip';
            $status = $flow->sendAndResolve($voided, $xml, $cdrPath);

            if ($status === VoidedStatus::Pendiente) {
                $this->dispatch('swal', [
                    'icon' => 'info',
                    'title' => 'En proceso',
                    'text' => 'SUNAT recibió la comunicación de baja pero aún la procesa. Su ticket quedó guardado.',
                ]);

                return;
            }

            $this->showResponse($voided);

            return;
        } catch (\Throwable $e) {
            $status = $flow->classifyFailure($voided, $e);

            if ($status === VoidedStatus::Pendiente) {
                $this->dispatch('swal', [
                    'icon' => 'info',
                    'title' => 'En proceso',
                    'text' => 'SUNAT recibió la comunicación de baja pero aún la procesa. Su ticket quedó guardado.',
                ]);

                return;
            }

            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage(),
            ]);
        }
    }

    public function showResponse(Voided $voided)
    {
        $title = 'Detalle de la comunicación de baja';

        $html = view('voideds.partials.sunatResponse', [
            'document' => $voided,
        ])->render();

        $icon = match ($voided->sunat_status) {
            VoidedStatus::Aceptada => 'info',
            VoidedStatus::Pendiente => 'warning',
            VoidedStatus::Rechazada => 'error',
            null => ($voided->sunatResponse['success'] ?? false) ? 'info' : 'error',
        };

        $this->dispatch('swal', [
            'icon' => $icon,
            'title' => $title,
            'html' => $html,
        ]);
    }

    public function builder(): Builder
    {
        return Voided::query()
            ->where('branch_id', session('branch')->id)
            ->where('company_id', session('company')->id)
            ->where('production', session('company')->production);
    }
}
