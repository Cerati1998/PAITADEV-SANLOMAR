<?php

namespace App\Livewire\Forms;

use App\Models\Invoice;
use App\Models\Voided;
use App\Services\Sunat\UtilService;
use App\Services\Sunat\VoidedService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
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
        $voidedGreenter = (new VoidedService())->getVoided($voided);

        //Directorio
        $directory = $this->production ? 'sunat/' : 'sunat/beta/';

        //Generar XML
        $xml = $util->getXmlSigned($voidedGreenter);
        $voided->hash = $util->getHashSign($xml);

        $voided->xml_path = $directory . 'xml/' . $voidedGreenter->getName() . '.xml';
        Storage::put($voided->xml_path, $xml);

        //PDF
        $pdf = $util->getReportPdf($voidedGreenter, $voided->hash);
        $voided->pdf_path = $directory . 'cpe/' . $voidedGreenter->getName() . '.pdf';
        Storage::put($voided->pdf_path, $pdf);
        $voided->save();

        //Enviar a SUNAT
        try {
            $see = $util->getSee();
            $result = $see->sendXmlFile($xml);

            if (!$result->isSuccess()) {
                $voided->sunatResponse = [
                    'success' => false,
                    'error' => [
                        'code' => $result->getError()->getCode(),
                        'message' => $result->getError()->getMessage()
                    ]
                ];

                $voided->save();

                return redirect()->route('vouchers.index');
            }

            $ticket = $result->getTicket();
            $result = $see->getStatus($ticket);

            $voided->sunatResponse = $util->getResponse($result);

            if ($result->getCdrZip()) {
                $voided->cdr_path = $directory . 'cdr/R-' . $voidedGreenter->getName() . '.zip';
                Storage::put($voided->cdr_path, $result->getCdrZip());
            }

            $voided->save();

            if($voided->sunatResponse['success'] && $voided->sunatResponse['cdrResponse']['code'] == '0')
            {
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

            return redirect()->route('voideds.index', $voided);

        } catch (\Exception $e) {
            session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error al enviar el comprobante',
                'text' => $e->getMessage()
            ]);

            return redirect()->route('vouchers.index');
        }
    }

    public function showResponse(Voided $voided)
    {
        $title = 'Detalle de la comunicación de baja';

        $html = view('voideds.partials.sunatResponse', [
            'document' => $voided
        ])->render();

        session()->flash('swal', [
            'icon' => $voided->sunatResponse['success'] ? 'info' : 'error',
            'title' => $title,
            'html' => $html
        ]);
    }
}
