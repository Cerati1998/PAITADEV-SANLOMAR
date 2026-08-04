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
use App\Services\Sunat\Xml\DespatchXmlBuilder;
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
                // Builder propio: el template vive en app/Services/Sunat/Templates
                // (versionado) en vez de vendor/, que el composer install del deploy
                // deja pristine. Ver DespatchXmlBuilder.
                $builder = new DespatchXmlBuilder();
                break;
            case Voided::class:
                $builder = new VoidedBuilder();
                break;
            default:
                throw new InvalidDocumentException('Not found template for ' . $className);
        }

        $xml = $builder->build($document);

        // DIAG-4068: log temporal para rastrear en que punto se corrompe la direccion (quitar tras diagnosticar).
        if ($className === Despatch::class) {
            \Illuminate\Support\Facades\Log::debug('DIAG-4068 config PHP runtime', [
                'default_charset' => ini_get('default_charset'),
                'mbstring.internal_encoding' => ini_get('mbstring.internal_encoding'),
                'mb_internal_encoding' => mb_internal_encoding(),
                'mbstring.http_output' => ini_get('mbstring.http_output'),
                'mbstring.http_input' => ini_get('mbstring.http_input'),
                'php_sapi' => php_sapi_name(),
                'php_version' => PHP_VERSION,
            ]);
            preg_match('/<cac:AddressLine>.*?<\/cac:AddressLine>/s', $xml, $matches);
            \Illuminate\Support\Facades\Log::debug('DIAG-4068 xml antes de firmar', [
                'address_lines' => $matches[0] ?? null,
                'address_lines_hex' => isset($matches[0]) ? bin2hex($matches[0]) : null,
                'mb_valid_utf8' => mb_check_encoding($xml, 'UTF-8'),
            ]);
        }

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);
        $xmlSigned = $signer->signXml($xml);

        // DIAG-4068: log temporal (quitar tras diagnosticar).
        if ($className === Despatch::class) {
            preg_match('/<cac:AddressLine>.*?<\/cac:AddressLine>/s', $xmlSigned, $matchesSigned);
            \Illuminate\Support\Facades\Log::debug('DIAG-4068 xml despues de firmar', [
                'address_lines' => $matchesSigned[0] ?? null,
                'address_lines_hex' => isset($matchesSigned[0]) ? bin2hex($matchesSigned[0]) : null,
                'mb_valid_utf8' => mb_check_encoding($xmlSigned, 'UTF-8'),
            ]);
        }

        return $xmlSigned;
    }

    public function getHashSign($xmlSigned)
    {
        return (new XmlUtils())->getHashSign($xmlSigned);
    }

    /**
     * Repara texto UTF-8 que fue doblemente codificado (p.ej. pegado desde Word/Excel
     * con codificación Windows-1252 y reinterpretado como ISO-8859-1), que produce
     * caracteres de control C1 (U+0080-U+009F) inválidos para XML y provoca que SUNAT
     * rechace el comprobante (errorCode 4068 en AddressLine, por ejemplo).
     *
     * Solo actúa cuando el texto ya contiene un control C1 literal (huella inequívoca
     * del doble-encoding) y el "downgrade" a bytes Latin-1 es reversible sin pérdida,
     * para no tocar texto correctamente tildado (ñ, á, –, etc.).
     */
    public static function repairUtf8($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'repairUtf8'], $data);
        }

        if (!is_string($data) || $data === '' || !mb_check_encoding($data, 'UTF-8')) {
            return $data;
        }

        if (!preg_match('/[\x{0080}-\x{009F}]/u', $data)) {
            return $data;
        }

        $downgraded = @mb_convert_encoding($data, 'ISO-8859-1', 'UTF-8');
        $roundtrip = @mb_convert_encoding($downgraded, 'UTF-8', 'ISO-8859-1');

        if ($roundtrip !== $data || $downgraded === $data || !mb_check_encoding($downgraded, 'UTF-8')) {
            return $data;
        }

        return $downgraded;
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

    public function getReportPdf(DocumentInterface $document, $hash, $tipoCambio = null)
    {
        // wkhtmltopdf via Greenter\Report\PdfReport: unica implementacion soportada.
        // Un intento previo de migrar a DomPDF (Pdf::loadHTML()->stream()) quedo
        // descartado porque stream() devuelve un Illuminate\Http\Response, no el
        // binario del PDF, lo que rompe cada Storage::put($path, $pdf) del proyecto.
        //
        // Se pasa public_path('custom/pdf') para que las plantillas propias vivan en el
        // repo y no dentro de vendor/ (que es gitignored y lo pisa cada composer install
        // del deploy: asi se perdio el "S/" fijo de la detraccion y salio en USD).
        // HtmlReport::getDirectories() encadena [custom/pdf, vendor/Templates], asi que
        // solo hace falta versionar las plantillas que realmente se sobrescriben; el
        // resto (guias, bajas, resumenes) sigue resolviendo desde vendor.
        $htmlReport = new HtmlReport(public_path('custom/pdf'));
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

        // El tipo de cambio ya no vive en el documento Greenter (ver DocumentService::
        // getInvoice): la plantilla lo lee desde params.user.tipoCambio y omite la fila
        // si no se envia.
        if ($tipoCambio) {
            $user['tipoCambio'] = $tipoCambio;
        }

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
