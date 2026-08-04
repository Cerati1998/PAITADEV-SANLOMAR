<?php

namespace App\Services\Sunat;

use Greenter\Model\DocumentInterface;
use Greenter\Model\Sale\Invoice;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use App\Services\Sunat\Xml\SunatXmlBuilder;
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
        // Builder propio para todos los tipos: las plantillas viven en
        // app/Services/Sunat/Templates (versionadas) en vez de vendor/, que el
        // composer install del deploy deja pristine. De Greenter se siguen usando
        // los modelos, la firma, el envio y la lectura del CDR. Ver SunatXmlBuilder.
        $xml = (new SunatXmlBuilder())->build($document);

        $signer = new SignedXml();
        $signer->setCertificate($this->company->certificate);

        return $signer->signXml($xml);
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

    public function getReportHtml($invoice, $hash, $tipoCambio = null)
    {

        /* $twigOptions = [
            'cache' => __DIR__ . '/cache',
            'strict_variables' => true,
        ]; */

        // Mismo directorio de plantillas que getReportPdf(): antes esta vista
        // apuntaba a public/custom, un fork mas viejo sin el bloque de detraccion,
        // asi que la previsualizacion HTML y el PDF descargado no coincidian.
        $report = new HtmlReport(app_path('Services/Sunat/Templates/pdf'));

        $resolver = new DefaultTemplateResolver();
        $report->setTemplate($resolver->getTemplate($invoice));

        $params = [
            'system' => [
                // logo_path, no image_path: esa columna no existe en companies, asi
                // que esta vista siempre caia al placeholder mientras el PDF si
                // mostraba el logo. Ver getReportPdf().
                'logo' => $this->company->logo_path ? Storage::get($this->company->logo_path) : file_get_contents(public_path('img/no-image.jpg')),
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

        // El tipo de cambio no vive en el documento Greenter, viaja por params
        // igual que en getReportPdf(); la plantilla omite la fila si no llega.
        if ($tipoCambio) {
            $params['user']['tipoCambio'] = $tipoCambio;
        }

        return $report->render($invoice, $params);
    }

    public function getReportPdf(DocumentInterface $document, $hash, $tipoCambio = null)
    {
        // wkhtmltopdf via Greenter\Report\PdfReport: unica implementacion soportada.
        // Un intento previo de migrar a DomPDF (Pdf::loadHTML()->stream()) quedo
        // descartado porque stream() devuelve un Illuminate\Http\Response, no el
        // binario del PDF, lo que rompe cada Storage::put($path, $pdf) del proyecto.
        //
        // Las plantillas propias viven en app/Services/Sunat/Templates/pdf y no dentro
        // de vendor/ (que es gitignored y lo pisa cada composer install del deploy: asi
        // se perdio el "S/" fijo de la detraccion y salio en USD). Tampoco en public/,
        // donde el servidor las entregaba como texto plano a cualquiera que pidiera la
        // URL del .twig.
        // HtmlReport::getDirectories() encadena [Templates/pdf, vendor/Templates], asi
        // que solo hace falta versionar las plantillas que realmente se sobrescriben; el
        // resto sigue resolviendo desde vendor.
        $htmlReport = new HtmlReport(app_path('Services/Sunat/Templates/pdf'));
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
