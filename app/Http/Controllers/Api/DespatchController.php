<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DespatchRequest;
use App\Models\Company;
use App\Models\Despatch;
use App\Services\Sunat\DespatchService;
use App\Services\Sunat\DocumentService;
use App\Services\Sunat\UtilService;
use Greenter\Report\XmlUtils;
use Greenter\Xml\Builder\DespatchBuilder;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DespatchController extends Controller
{
    public $company;

    public function __construct()
    {
        $company = auth('sanctum')->user();

        if (!($company instanceof Company)) {

            return response()->json([
                'message' => 'El token no está asociado a una empresa.',
            ], 400);
        }

        $this->company = $company;
    }

    public function send(DespatchRequest $request)
    {
        $data = $request->all();
        $data['company_id'] = $this->company->id;
        $data['production'] = $this->company->production;

        $despatch = Despatch::create($data);

        $util = new UtilService($this->company);
        $document = new DocumentService();

        $despatchGreenter = $document->getDespatch($despatch);

        //Directorio
        $directory = $this->company->production ? 'sunat/' : 'sunat/beta/';

        //Generar XML
        $xml = $util->getXmlSigned($despatchGreenter);
        $despatch->hash = $util->getHashSign($xml);

        //Guardar XML
        $despatch->xml_path = $directory . 'xml/' . $despatchGreenter->getName() . '.xml';
        Storage::put($despatch->xml_path, $xml);

        //Guardar PDF
        $pdf = $util->getReportPdf($despatchGreenter, $despatch->hash);
        $despatch->pdf_path = $directory . 'cpe/' . $despatchGreenter->getName() . '.pdf';
        Storage::put($despatch->pdf_path, $pdf);
        $despatch->save();

        //Enviar a SUNAT
        try {
            $api = $util->getSeeApi();
            $result = $api->sendXml($despatchGreenter->getName(), $xml);

            if (!$result->isSuccess()) {

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();

                return $despatch->sunatResponse;
            }

            $ticket = $result->getTicket();
            $result = $api->getStatus($ticket);

            if (!$result->isSuccess()) {

                $despatch->sunatResponse = $util->getErrorResponse($result);
                $despatch->save();

                return $despatch->sunatResponse;
            }

            // Guardamos el CDR
            $despatch->cdr_path = $directory . 'cdr/R-' . $despatchGreenter->getName() . '.zip';
            Storage::put($despatch->cdr_path, $result->getCdrZip());

            $cdr = $result->getCdrResponse();
            $despatch->sunatResponse = $util->readCdr($cdr);
            $despatch->save();

            return response()->json([
                'sunatResponse' => $despatch->sunatResponse,
                'pdf_url' => Storage::url($despatch->pdf_path),
                'xml_url' => Storage::url($despatch->xml_path),
                'cdr_url' => Storage::url($despatch->cdr_path),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);

        }

    }

    public function xml(DespatchRequest $request)
    {
        $data = $request->all();
        $data['company'] = [
            'ruc' => $this->company->ruc,
            'razonSocial' => $this->company->razonSocial,
            'nombreComercial' => $this->company->nombreComercial,
            'address' => [
                'direccion' => $this->company->direccion,
                'distrito' => $this->company->district->name,
                'provincia' => $this->company->district->province->name,
                'departamento' => $this->company->district->province->department->name,
                'ubigueo' => $this->company->ubigeo
            ]
        ];

        $util = new UtilService($this->company);
        $document = new DocumentService();

        $despatchGreenter = $document->getDespatch($data);

        //Generar XML
        $xml = $util->getXmlSigned($despatchGreenter);
        $hash = $util->getHashSign($xml);

        return response()->json([
            'xml' => $xml,
            'hash' => $hash,
        ]);
    }

    public function pdf(DespatchRequest $request)
    {
        $data = $request->all();
        $data['company'] = [
            'ruc' => $this->company->ruc,
            'razonSocial' => $this->company->razonSocial,
            'nombreComercial' => $this->company->nombreComercial,
            'address' => [
                'direccion' => $this->company->direccion,
                'distrito' => $this->company->district->name,
                'provincia' => $this->company->district->province->name,
                'departamento' => $this->company->district->province->department->name,
                'ubigueo' => $this->company->ubigeo
            ]
        ];

        $util = new UtilService($this->company);
        $document = new DocumentService();

        $despatchGreenter = $document->getDespatch($data);

        //Generar XML
        $xml = $util->getXmlSigned($despatchGreenter);
        $hash = $util->getHashSign($xml);

        return response()->json([
            'html' => $util->getReportHtml($despatchGreenter, $hash),
        ]);
    }
}
