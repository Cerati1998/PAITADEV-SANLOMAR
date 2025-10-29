<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SummaryRequest;
use App\Models\Company;
use App\Services\Sunat\SummaryService;
use Greenter\Report\XmlUtils;
use Greenter\Xml\Builder\SummaryBuilder;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class SummaryController extends Controller
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

    public function send(SummaryRequest $request)
    {
        $sunat = new SummaryService($this->company);
        $summary = $sunat->getSummary(request()->all());

        $see = $sunat->getSee();
        
        $result = $see->send($summary);
        
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

    public function xml(SummaryRequest $request)
    {
        $sunat = new SummaryService($this->company);
        $summary = $sunat->getSummary(request()->all());

        $builder = new SummaryBuilder();
        $xml = $builder->build($summary);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);

        $xmlSigned = $signer->signXml($xml);
        $hash = (new XmlUtils())->getHashSign($xmlSigned);

        return response()->json([
            'xml' => $xmlSigned,
            'hash' => $hash,
        ]);
    }

    public function pdf(SummaryRequest $request)
    {
        $sunat = new SummaryService($this->company);
        $summary = $sunat->getSummary(request()->all());

        $builder = new SummaryBuilder();
        $xml = $builder->build($summary);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);

        $xmlSigned = $signer->signXml($xml);
        $hash = (new XmlUtils())->getHashSign($xmlSigned);

        return response()->json([
            'html' => $sunat->getReportHtml($summary, $hash, $this->company),
        ]);
    }
}
