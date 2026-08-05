<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Sunat\VoidedService;
use App\Services\Sunat\Xml\SunatXmlBuilder;
use Greenter\Report\XmlUtils;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class VoidedController extends Controller
{
    public $company;

    public function __construct()
    {
        $company = auth('sanctum')->user();

        if (! ($company instanceof Company)) {

            throw new AuthenticationException('El token no está asociado a una empresa.');
        }

        $this->company = $company;
    }

    /**
     * Aplica el mismo contrato de entrada que VoidedForm::send().
     *
     * Esta superficie construye el documento directamente desde
     * request()->all() y lo firma, así que sin esta validación desMotivoBaja
     * seguiría llegando sin filtrar al CDATA del XML que se envía a SUNAT
     * aunque el flujo web ya estuviera cerrado.
     */
    private function validateDetails(): void
    {
        request()->validate(
            array_merge(
                ['details' => 'required|array|min:1'],
                VoidedService::detailRules()
            ),
            VoidedService::detailMessages()
        );
    }

    public function send()
    {
        $this->validateDetails();

        $sunat = new VoidedService($this->company);
        $voided = $sunat->getVoided(request()->all());

        $see = $sunat->getSee();

        $result = $see->send($voided);

        if ($result->isSuccess()) {
            $ticket = $result->getTicket();
            $result = $see->getStatus($ticket);

            $response = $sunat->getResponse($result);
        } else {
            $response['success'] = false;
            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage(),
            ];
        }

        return response()->json($response);
    }

    public function xml()
    {
        $this->validateDetails();

        $sunat = new VoidedService($this->company);
        $voided = $sunat->getVoided(request()->all());

        $builder = new SunatXmlBuilder;
        $xml = $builder->build($voided);

        $signer = new SignedXml;
        $signer->setCertificate($this->company->certificate);

        $xmlSigned = $signer->signXml($xml);
        $hash = (new XmlUtils)->getHashSign($xmlSigned);

        return response()->json([
            'xml' => $xmlSigned,
            'hash' => $hash,
        ]);
    }

    public function pdf()
    {
        $this->validateDetails();

        $sunat = new VoidedService($this->company);
        $voided = $sunat->getVoided(request()->all());

        $builder = new SunatXmlBuilder;
        $xml = $builder->build($voided);

        $signer = new SignedXml;
        $signer->setCertificate($this->company->certificate);

        $xmlSigned = $signer->signXml($xml);
        $hash = (new XmlUtils)->getHashSign($xmlSigned);

        return $sunat->getReportHtml($voided, $hash, $this->company);
    }
}
