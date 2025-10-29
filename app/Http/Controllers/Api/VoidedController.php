<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Sunat\VoidedService;
use Greenter\Report\XmlUtils;
use Greenter\Xml\Builder\VoidedBuilder;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class VoidedController extends Controller
{
    public $company;

    public function __construct()
    {
        $company = auth('sanctum')->user();

        if (!($company instanceof Company)) {

            throw new AuthenticationException('El token no está asociado a una empresa.');
        }

        $this->company = $company;
    }

    public function send()
    {
        $sunat = new VoidedService($this->company);
        $voided = $sunat->getVoided(request()->all());

        $see = $sunat->getSee();
        
        $result = $see->send($voided);

        if ($result->isSuccess()) {
            $ticket = $result->getTicket();
            $result = $see->getStatus($ticket);

            $response = $sunat->getResponse($result);
        }else{
            $response['success'] = false;
            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];
        }

        return response()->json($response);
    }

    public function xml()
    {
        $sunat = new VoidedService($this->company);
        $voided = $sunat->getVoided(request()->all());

        $builder = new VoidedBuilder();
        $xml = $builder->build($voided);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);

        $xmlSigned = $signer->signXml($xml);
        $hash = (new XmlUtils())->getHashSign($xmlSigned);

        return response()->json([
            'xml' => $xmlSigned,
            'hash' => $hash,
        ]);
    }

    public function pdf()
    {
        $sunat = new VoidedService($this->company);
        $voided = $sunat->getVoided(request()->all());

        $builder = new VoidedBuilder();
        $xml = $builder->build($voided);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);

        $xmlSigned = $signer->signXml($xml);
        $hash = (new XmlUtils())->getHashSign($xmlSigned);

        return $sunat->getReportHtml($voided, $hash, $this->company);
    }
}
