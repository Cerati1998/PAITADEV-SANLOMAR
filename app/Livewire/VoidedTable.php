<?php

namespace App\Livewire;

use App\Models\Invoice;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Voided;
use App\Services\Sunat\UtilService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class VoidedTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setAdditionalSelects(['id']);
        $this->setDefaultSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Fecha Generacion", "fecGeneracion")
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make("Fecha Comunicación", "fecComunicacion")
                ->format(function ($value) {
                    return $value->format('d/m/Y');
                })
                ->sortable(),

            Column::make('correlativo')
                ->format(
                    function ($value) {
                        return 'Comunicación de baja: ' . $value;
                    }
                ),

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
        $directory = $voided->production ? 'sunat/' : 'sunat/beta/';
        $name = session('company')->ruc . "-RC-" . $voided->fecGeneracion->format('Ymd') . '-' . $voided->correlativo;

        //Enviar a SUNAT
        try {

            $util = new UtilService(session('company'));
            $see = $util->getSee();
            $result = $see->sendXmlFile($voided->xml);

            if (!$result->isSuccess()) {

                $voided->update([
                    'sunatResponse' => [
                        'success' => false,
                        'error' => [
                            'code' => $result->getError()->getCode(),
                            'message' => $result->getError()->getMessage()
                        ]
                    ]
                ]);

                $this->showResponse($voided);

                return;
            }

            $ticket = $result->getTicket();
            $result = $see->getStatus($ticket);

            $voided->sunatResponse = $util->getResponse($result);

            if ($result->getCdrZip()) {
                $voided->cdr_path = $directory . 'cdr/R-' . $name . '.zip';
                Storage::put($voided->cdr_path, $result->getCdrZip());
            }

            $voided->save();

            if ($voided->sunatResponse['success'] && $voided->sunatResponse['cdrResponse']['code'] == '0') {
                foreach ($voided->details as $item) {
                    Invoice::where('tipoDoc', $item['tipoDoc'])
                        ->where('serie', $item['serie'])
                        ->where('correlativo', $item['correlativo'])
                        ->where('branch_id', session('branch')->id)
                        ->where('company_id', session('company')->id)
                        ->update(['voided' => true]);
                }
            }

            $this->showResponse($voided);

            return;
        } catch (\Exception $e) {
            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage()
            ]);
        }
    }

    public function showResponse(Voided $voided)
    {
        $title = 'Detalle de la comunicación de baja';

        $html = view('voideds.partials.sunatResponse', [
            'document' => $voided
        ])->render();

        $this->dispatch('swal', [
            'icon' => $voided->sunatResponse['success'] ? 'info' : 'error',
            'title' => $title,
            'html' => $html
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
