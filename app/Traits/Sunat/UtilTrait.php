<?php

namespace App\Traits\Sunat;

use App\Models\Detraction;
use App\Models\PaymentMethod;
/* use App\Services\Greenter\Report\HtmlReport; */
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Report\HtmlReport;
use Greenter\Report\PdfReport;
use Greenter\Report\Resolver\DefaultTemplateResolver;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;

trait UtilTrait
{
    public function getSee()
    {
        $endpoint = $this->company->production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA;

        $see = new See();
        $see->setCertificate($this->company->certificate);
        $see->setService($endpoint);

        $see->setClaveSOL(
            $this->company->ruc, 
            $this->company->sol_user, 
            $this->company->sol_pass
        );

        return $see;
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
                $this->company->production ? $this->company->user_sol : 'MODDATOS', 
                $this->company->production ? $this->company->password_sol : 'MODDATOS'
            )
            ->setCertificate($this->company->certificate);
    }

    public function getCompany()
    {
        return (new Company())
            ->setRuc($this->company->ruc)
            ->setRazonSocial($this->company->razonSocial)
            ->setNombreComercial($this->company->nombreComercial)
            ->setAddress(
                (new Address())
                ->setDireccion($this->company->direccion)
                ->setDistrito($this->company->district->name)
                ->setProvincia($this->company->district->province->name)
                ->setDepartamento($this->company->district->province->department->name)
                ->setUbigueo($this->company->ubigeo)
            );
    }

    public function getClient($client)
    {
        if (!$client) {
            return null;
        }

        $greenClient = (new Client())
            ->setTipoDoc($client['tipoDoc'] == '-' ? 0 : $client['tipoDoc']) // DNI - Catalog. 06
            ->setNumDoc($client['numDoc'] ?: '-')
            ->setRznSocial($client['rznSocial']);

        if (isset($client['address'])) {
            $greenClient->setAddress(
                (new Address())
                ->setDireccion($client['address']['direccion'] ?? null)
                ->setDepartamento($client['address']['departamento'] ?? null)
                ->setProvincia($client['address']['provincia'] ?? null)
                ->setDistrito($client['address']['distrito'] ?? null)
                ->setUbigueo($client['address']['ubigueo'] ?? null)
            );
        }

        return $greenClient;
    }

    public function getResponse($result, $ticket = null)
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

        // Guardamos el CDR
        $cdr = $result->getCdrResponse();

        $response['cdrResponse'] = [
            'id' => $cdr->getId(),
            'code' => (int)$cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes(),
        ];

        return $response;
    }

    public function getResponseTicket($result, $see)
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

        $ticket = $result->getTicket();
        $result = $see->getStatus($ticket);


    }

    public function getReportHtml($invoice, $hash, $company){

        /* $twigOptions = [
            'cache' => __DIR__ . '/cache',
            'strict_variables' => true,
        ]; */

        $report = new HtmlReport(public_path('custom'));

        $resolver = new DefaultTemplateResolver();
        $report->setTemplate($resolver->getTemplate($invoice));

        $params = [
            'system' => [
                'logo' => $company->image_path ? Storage::get($company->image_path) : file_get_contents(public_path('img/no-image.jpg')),
                'hash' => $hash, // Valor Resumen 
            ],
            'user' => [
                'header'     => 'Telf: <b>987601368</b>', // Texto que se ubica debajo de la dirección de empresa
                'extras'     => [
                    // Leyendas adicionales
                    [
                        'name' => 'CONDICION DE PAGO', 
                        'value' => 'Efectivo'
                    ],
                    [
                        'name' => 'VENDEDOR' , 
                        'value' => $company->razonSocial
                    ],
                ],
                'footer'     => 'Gracias por su compra.', // Texto que se ubica debajo de las observaciones
            ]
        ];

        return $report->render($invoice, $params);
    }

    public function getReportPdf($invoice, $hash, $company)
    {
        /* $htmlReport = new HtmlReport(public_path('custom')); */
        $htmlReport = new HtmlReport();
        $resolver = new DefaultTemplateResolver();
        $htmlReport->setTemplate($resolver->getTemplate($invoice));

        $report = new PdfReport($htmlReport);

        // Options: Ver mas en https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
        $report->setOptions([
            'no-outline',
            'viewport-size' => '1280x1024',
            'page-width' => '21cm',
            'page-height' => '29.7cm',
        ]);

        $report->setBinPath(env('WKHTMLTOPDF_BINARIES')); // Ruta relativa o absoluta de wkhtmltopdf

        $extras = [];
        if (in_array($invoice->getTipoDoc(), ['01', '03'])) {

            $formaPago = $invoice->getFormaPago();
            $extras[] = [
                'name' => 'CONDICION DE PAGO', 
                'value' => $formaPago ? $formaPago->getTipo() : 'Efectivo'
            ];
        }

        $extras[] = [
            'name' => 'VENDEDOR' , 
            'value' => $company->razonSocial
        ];

        $user['extras'] = $extras;

        if ($company->invoice_header) {
            $user['header'] = $company->invoice_header;
        }

        if ($company->invoice_footer) {
            $user['footer'] = '<p>' . $company->invoice_footer . '</p>';
        }

        $params = [
            'system' => [
                'logo' => $company->logo_path ? Storage::get($company->logo_path) : file_get_contents(public_path('img/no-image.jpg')),
                'hash' => $hash, // Valor Resumen 
            ],
            'user' => $user
        ];

        return $report->render($invoice, $params);

    }
}
