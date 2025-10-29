<?php

namespace App\Services\Sunat;

use App\Traits\Sunat\UtilTrait;
use DateTime;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;

class VoidedService
{
    public function getVoided($data)
    {
        return (new Voided())
            ->setCorrelativo($data['correlativo'])
            ->setFecGeneracion(new DateTime($data['fecGeneracion'] ?? null))
            ->setFecComunicacion(new DateTime($data['fecComunicacion'] ?? null))
            ->setCompany($this->getCompany($data['company']))
            ->setDetails($this->getVoidedDetails($data['details']));
    }

    public function getCompany($company)
    {
        return (new Company())
            ->setRuc($company['ruc'])
            ->setRazonSocial($company['razonSocial'])
            ->setNombreComercial($company['nombreComercial'])
            ->setAddress(
                (new Address())
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
            $greenDetails[] = (new VoidedDetail())
                ->setTipoDoc($detail['tipoDoc']) // Factura - Catalog. 01
                ->setSerie($detail['serie'])
                ->setCorrelativo($detail['correlativo'])
                ->setDesMotivoBaja($detail['desMotivoBaja']);
        }

        return $greenDetails;
    }
}