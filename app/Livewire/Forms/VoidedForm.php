<?php

namespace App\Livewire\Forms;

use App\Enums\VoidedStatus;
use App\Models\Voided;
use App\Services\Sunat\UtilService;
use App\Services\Sunat\VoidedService;
use App\Services\Sunat\VoidedTicketFlowService;
use Illuminate\Support\Facades\Storage;
use Livewire\Form;

class VoidedForm extends Form
{
    public $correlativo;

    public $fecGeneracion;

    public $fecComunicacion;

    public $details = [];

    public $sunatResponse;

    public $branch_id;

    public $company_id;

    public $production;

    public function send()
    {
        $this->validate([
            'details.*.desMotivoBaja' => 'required|min:3|max:250',
        ]);

        $voided = Voided::create($this->all());
        $util = new UtilService(session('company'));
        $flow = new VoidedTicketFlowService($util);

        //Enviar a SUNAT
        try {
            $voidedGreenter = (new VoidedService)->getVoided($voided);

            //Directorio
            $directory = $this->production ? 'sunat/' : 'sunat/beta/';

            //Generar XML
            $xml = $util->getXmlSigned($voidedGreenter);
            $voided->hash = $util->getHashSign($xml);

            $voided->xml_path = $directory.'xml/'.$voidedGreenter->getName().'.xml';
            Storage::put($voided->xml_path, $xml);

            //PDF
            $pdf = $util->getReportPdf($voidedGreenter, $voided->hash);
            $voided->pdf_path = $directory.'cpe/'.$voidedGreenter->getName().'.pdf';
            Storage::put($voided->pdf_path, $pdf);
            $voided->save();

            $cdrPath = $directory.'cdr/R-'.$voidedGreenter->getName().'.zip';
            $status = $flow->sendAndResolve($voided, $xml, $cdrPath);

            if ($status === VoidedStatus::Pendiente) {
                session()->flash('swal', [
                    'icon' => 'info',
                    'title' => 'En proceso',
                    'text' => 'SUNAT recibió la comunicación de baja pero aún la procesa. Su ticket quedó guardado.',
                ]);

                return redirect()->route('voideds.index');
            }

            $this->showResponse($voided);

            return redirect()->route('voideds.index');

        } catch (\Throwable $e) {
            $status = $flow->classifyFailure($voided, $e);

            if ($status === VoidedStatus::Pendiente) {
                session()->flash('swal', [
                    'icon' => 'info',
                    'title' => 'En proceso',
                    'text' => 'SUNAT recibió la comunicación de baja pero aún la procesa. Su ticket quedó guardado.',
                ]);

                return redirect()->route('voideds.index');
            }

            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error al generar el comprobante',
                'text' => $e->getMessage(),
            ]);

            return redirect()->route('voideds.index');
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

        session()->flash('swal', [
            'icon' => $icon,
            'title' => $title,
            'html' => $html,
        ]);
    }
}
