<?php

namespace App\Services\Sunat;

use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Voided\Voided;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\Report\Resolver\InvalidDocumentException;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\Xml\Builder\DespatchBuilder;
use Greenter\Xml\Builder\InvoiceBuilder;
use Greenter\Xml\Builder\NoteBuilder;
use Greenter\Xml\Builder\VoidedBuilder;
use Greenter\XMLSecLibs\Sunat\SignedXml;
use Illuminate\Support\Facades\Storage;

class UtilService
{
    protected $see;
    protected $company;

    public function __construct($company)
    {
        $this->company = $company;
    }

    public function getSee()
    {
        $this->see = new See();
        $this->see->setCertificate($this->company->production ? $this->company->certificate : file_get_contents(public_path('certificates/certificate.pem')));
        $this->see->setService($this->company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);

        $this->see->setClaveSOL(
            $this->company->ruc,
            $this->company->production ? $this->company->sol_user : 'MODDATOS',
            $this->company->production ? $this->company->sol_pass : 'MODDATOS'
        );

        return $this->see;
    }

    public function getSeeApi()
    {
        $api = new \Greenter\Api($this->company->production ? [
            'auth' => 'https://api-seguridad.sunat.gob.pe/v1',
            'cpe' => 'https://api-cpe.sunat.gob.pe/v1',
        ] : [
            'auth' => 'https://gre-test.nubefact.com/v1',
            'cpe' => 'https://gre-test.nubefact.com/v1',
        ]);

        return $api->setBuilderOptions([
            'strict_variables' => true,
            'optimizations' => 0,
            'debug' => true,
            'cache' => false,
        ])
            ->setApiCredentials(
                $this->company->production ? $this->company->client_id : 'test-85e5b0ae-255c-4891-a595-0b98c65c9854',
                $this->company->production ? $this->company->client_secret : 'test-Hty/M6QshYvPgItX2P0+Kw=='
            )
            ->setClaveSOL(
                $this->company->ruc,
                $this->company->production ? $this->company->sol_user : 'MODDATOS',
                $this->company->production ? $this->company->sol_pass : 'MODDATOS'
            )
            ->setCertificate($this->company->production ? $this->company->certificate : file_get_contents(public_path('certificates/certificate.pem')));
    }

    public function getXmlSigned(DocumentInterface $document)
    {
        $className = get_class($document);

        switch ($className) {
            case Invoice::class:
                $builder = new InvoiceBuilder();
                break;
            case Note::class:
                $builder = new NoteBuilder();
                break;
            case Despatch::class:
                $builder = new DespatchBuilder();
                break;
            case Voided::class:
                $builder = new VoidedBuilder();
                break;
            default:
                throw new InvalidDocumentException('Not found template for ' . $className);
        }

        $xml = $builder->build($document);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);
        $xmlSigned = $signer->signXml($xml);

        return $xmlSigned;
    }

    public function getHashSign($xmlSigned)
    {
        return (new XmlUtils())->getHashSign($xmlSigned);
    }

    public function getResponse($result)
    {
        $response['success'] = $result->isSuccess();

        // Verificamos que la conexión con SUNAT fue exitosa.
        if (!$response['success']) {
            // Mostrar error al conectarse a SUNAT.
            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];

            return $response;
        }

        // Leemos el CDR de SUNAT.
        $cdr = $result->getCdrResponse();

        $response['cdrResponse'] = [
            'id' => $cdr->getId(),
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes(),
        ];

        return $response;
    }

    public function getResponseVoided($result)
    {
        if ($result->isSuccess()) {
            $ticket = $result->getTicket();
            $result = $this->see->getStatus($ticket);
            $response = $this->getResponse($result);
        } else {
            $response['success'] = false;
            $response['error'] = [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ];
        }

        return $response;
    }

    public function getErrorResponse($result)
    {
        return [
            'success' => false,
            'error' => [
                'code' => $result->getError()->getCode(),
                'message' => $result->getError()->getMessage()
            ]
        ];
    }

    public function readCdr($cdr)
    {
        $response = [
            'success' => true,
            'cdrResponse' => [
                'id' => $cdr->getId(),
                'code' => (int)$cdr->getCode(),
                'description' => $cdr->getDescription(),
                'notes' => $cdr->getNotes(),
            ]
        ];

        return $response;
    }

    public function getReportHtml($invoice, $hash)
    {

        /* $twigOptions = [
            'cache' => __DIR__ . '/cache',
            'strict_variables' => true,
        ]; */

        $report = new HtmlReport(public_path('custom'));

        $resolver = new DefaultTemplateResolver();
        $report->setTemplate($resolver->getTemplate($invoice));

        $params = [
            'system' => [
                'logo' => $this->company->image_path ? Storage::get($this->company->image_path) : file_get_contents(public_path('img/no-image.jpg')),
                'hash' => $hash, // Valor Resumen 
            ],
            'user' => [
                'header'     => 'Telf: <b>987601368</b>', // Texto que se ubica debajo de la dirección de empresa
                'extras'     => [
                    // Leyendas adicionales
                    [
                        'name' => 'CONDICION',
                        'value' => 'Efectivo'
                    ],
                    [
                        'name' => 'VENDEDOR',
                        'value' => $this->company->razonSocial
                    ],
                ],
                'footer' => 'Gracias por su compra.', // Texto que se ubica debajo de las observaciones
            ]
        ];

        return $report->render($invoice, $params);
    }

    public function getReportPdf(DocumentInterface $document, $hash)
    {
        // wkhtmltopdf via Greenter\Report\PdfReport: unica implementacion soportada.
        // Un intento previo de migrar a DomPDF (Pdf::loadHTML()->stream()) quedo
        // descartado porque stream() devuelve un Illuminate\Http\Response, no el
        // binario del PDF, lo que rompe cada Storage::put($path, $pdf) del proyecto.
        $htmlReport = new HtmlReport();
        $resolver = new DefaultTemplateResolver();
        $htmlReport->setTemplate($resolver->getTemplate($document));

        $report = new PdfReport($htmlReport);

        // Options: Ver mas en https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        $report->setOptions([
            'no-outline',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
        ]);

        $report->setBinPath(config('services.wkhtmltopdf.binaries')); // Ruta relativa o absoluta de wkhtmltopdf

        $extras = [];

        $className = get_class($document);
        if ($className == Invoice::class) {
            $formaPago = $document->getFormaPago();
            $extras[] = [
                'name' => 'CONDICION',
                'value' => $formaPago ? $formaPago->getTipo() : 'Efectivo'
            ];
        }

        $extras[] = [
            'name' => 'VENDEDOR',
            'value' => $this->company->razonSocial
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
                'logo' => $this->company->logo_path ? Storage::get($this->company->logo_path) : file_get_contents(public_path('img/no-image.jpg')),
                'hash' => $hash, // Valor Resumen 
            ],
            'user' => $user
        ];

        return $report->render($document, $params);
    }
}
