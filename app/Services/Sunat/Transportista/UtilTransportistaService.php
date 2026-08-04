<?php

namespace App\Services\Sunat\Transportista;

use App\Services\Sunat\UtilService;
use Greenter\Model\DocumentInterface;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Support\Facades\Storage;

/**
 * Extiende UtilService (envío GRE API, hash, lectura de CDR, credenciales de
 * la empresa) y solo redefine lo que Greenter no soporta para tipoDoc 31:
 * la generación del XML (usa TransportistaXmlBuilder en vez de
 * Greenter\Xml\Builder\DespatchBuilder) y el reporte PDF (Greenter\Report\
 * Resolver\DefaultTemplateResolver no reconoce esta clase de documento).
 *
 * getSee(), getSeeApi(), getHashSign(), getResponse(), getErrorResponse(),
 * readCdr() se heredan sin cambios: el envío a SUNAT (GRE API REST) es el
 * mismo servicio y las mismas credenciales para tipoDoc 09 y 31 — ver
 * guia-transportista.md, sección "Endpoints SUNAT".
 */
class UtilTransportistaService extends UtilService
{
    public function getXmlSigned(DocumentInterface $document)
    {
        $builder = new Xml\TransportistaXmlBuilder();
        $xml = $builder->build($document);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);

        return $signer->signXml($xml);
    }

    public function getReportHtml($document, $hash)
    {
        $report = new HtmlReport(app_path('Services/Sunat/Templates/pdf'));
        $report->setTemplate('despatchTransportista.html.twig');

        $params = [
            'system' => [
                // logo_path, no image_path: esa columna no existe en companies.
                'logo' => $this->company->logo_path ? Storage::get($this->company->logo_path) : file_get_contents(public_path('img/no-image.jpg')),
                'hash' => $hash,
            ],
            'user' => [
                'extras' => [
                    [
                        'name' => 'VENDEDOR',
                        'value' => $this->company->razonSocial,
                    ],
                ],
            ],
        ];

        return $report->render($document, $params);
    }

    public function getReportPdf(DocumentInterface $document, $hash)
    {
        // wkhtmltopdf via Greenter\Report\PdfReport, igual que UtilService::getReportPdf()
        // (Pdf::loadHTML()->stream() de DomPDF devuelve un Response, no el binario del
        // PDF, y rompe el Storage::put($path, $pdf) de DespatchTransportistaForm).
        $htmlReport = new HtmlReport(app_path('Services/Sunat/Templates/pdf'));
        $htmlReport->setTemplate('despatchTransportista.html.twig');

        $report = new PdfReport($htmlReport);

        $report->setOptions([
            'no-outline',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
        ]);

        $report->setBinPath(config('services.wkhtmltopdf.binaries'));

        $extras = [
            [
                'name' => 'VENDEDOR',
                'value' => $this->company->razonSocial,
            ],
        ];

        $user['extras'] = $extras;

        if ($this->company->invoice_header) {
            $user['header'] = $this->company->invoice_header;
        }

        if ($this->company->invoice_footer) {
            $user['footer'] = '<p>' . $this->company->invoice_footer . '</p>';
        }

        $params = [
            'system' => [
                'logo' => $this->company->logo_path
                    ? Storage::get($this->company->logo_path)
                    : file_get_contents(public_path('img/no-image.jpg')),
                'hash' => $hash,
            ],
            'user' => $user,
        ];

        return $report->render($document, $params);
    }
}
