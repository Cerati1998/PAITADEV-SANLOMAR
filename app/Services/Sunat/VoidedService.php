<?php

namespace App\Services\Sunat;

use DateTime;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;

class VoidedService
{
    /**
     * Contrato de entrada de cada línea de la comunicación de baja.
     *
     * Vive aquí porque esta es la clase que convierte los datos en el documento
     * Greenter que después se firma, y por tanto la comparten los dos únicos
     * puntos por los que el dato entra al sistema: VoidedForm::send() (web) y
     * Api\VoidedController (API). Una sola definición evita que las dos
     * superficies diverjan.
     *
     * desMotivoBaja acaba dentro de un CDATA del XML firmado que se envía a
     * SUNAT (Templates/voided.xml.twig). La lista de permitidos excluye ']',
     * '<' y '>', de modo que ']]>' —la única secuencia capaz de cerrar el CDATA
     * e inyectar en el comprobante líneas de baja que no están en details— es
     * imposible de construir. El not_regex explícito la deja documentada y
     * sobrevive a que alguien amplíe la lista más adelante.
     *
     * El máximo es 100 y no 250: 100 es el límite de SUNAT para «Motivo o
     * sustento de baja», y superarlo produce rechazos evitables.
     */
    public static function detailRules(): array
    {
        return [
            'details.*.tipoDoc' => 'required|string|size:2',
            'details.*.serie' => 'required|string|max:4',
            'details.*.correlativo' => 'required',
            'details.*.desMotivoBaja' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'not_regex:/\]\]>/',
                'regex:/^[\pL\pN \.,;:\-\/#()%&+@_°º]+$/u',
            ],
        ];
    }

    /**
     * Mensajes de las reglas de detailRules() que, sin traducir, delatarían la
     * expresión regular al usuario.
     */
    public static function detailMessages(): array
    {
        return [
            'details.*.desMotivoBaja.not_regex' => 'El motivo de baja contiene caracteres no permitidos.',
            'details.*.desMotivoBaja.regex' => 'El motivo de baja contiene caracteres no permitidos.',
            'details.*.desMotivoBaja.max' => 'El motivo de baja no puede superar los 100 caracteres (límite de SUNAT).',
        ];
    }

    public function getVoided($data)
    {
        return (new Voided)
            ->setCorrelativo($data['correlativo'])
            ->setFecGeneracion(new DateTime($data['fecGeneracion'] ?? null))
            ->setFecComunicacion(new DateTime($data['fecComunicacion'] ?? null))
            ->setCompany($this->getCompany($data['company']))
            ->setDetails($this->getVoidedDetails($data['details']));
    }

    public function getCompany($company)
    {
        return (new Company)
            ->setRuc($company['ruc'])
            ->setRazonSocial($company['razonSocial'])
            ->setNombreComercial($company['nombreComercial'])
            ->setAddress(
                (new Address)
                    ->setDireccion($company['address']['direccion'])
                    ->setDistrito($company['address']['distrito'])
                    ->setProvincia($company['address']['provincia'])
                    ->setDepartamento($company['address']['departamento'])
                    ->setUbigueo($company['address']['ubigueo'])
            );
    }

    public function getVoidedDetails($details)
    {
        $greenDetails = [];

        foreach ($details as $detail) {
            $greenDetails[] = (new VoidedDetail)
                ->setTipoDoc($detail['tipoDoc']) // Factura - Catalog. 01
                ->setSerie($detail['serie'])
                ->setCorrelativo($detail['correlativo'])
                ->setDesMotivoBaja($detail['desMotivoBaja']);
        }

        return $greenDetails;
    }
}
