<?php

namespace App\Services\Sunat;

use App\Traits\Sunat\UtilTrait;
use DateTime;
use Greenter\Model\Sale\Document;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;
use Greenter\Model\Summary\SummaryPerception;

class SummaryService
{
    use UtilTrait;

    public function getSummary($data)
    {
        return (new Summary())
            ->setFecGeneracion(new DateTime($data['fecGeneracion'])) // Fecha de emisión de las boletas.
            ->setFecResumen(new DateTime($data['fecResumen'])) // Fecha de envío del resumen diario.
            ->setCorrelativo($data['correlativo']) // Correlativo, necesario para diferenciar de otros Resumen diario del mismo día.
            ->setCompany($this->getCompany())
            ->setMoneda($data['moneda']) // Moneda: PEN
            ->setDetails($this->getDetails($data['details']));
    }

    public function getDetails($details)
    {
        $greenDetails = [];

        foreach ($details as $detail) {
            $greenDetails[] = (new SummaryDetail())
                ->setTipoDoc($detail['tipoDoc']) // Nota de Credito
                ->setSerieNro($detail['serieNro'])
                ->setClienteTipo($detail['clienteTipo']) // Tipo documento identidad: DNI
                ->setClienteNro($detail['clienteNro']) // Nro de documento identidad
                ->setDocReferencia($this->getDocReferencia($detail['docReferencia'] ?? null))
                ->setPercepcion($this->getPercepcion($detail['percepcion'] ?? null))
                ->setEstado($detail['estado']) // Catalog. 19
                ->setTotal($detail['total'])
                ->setMtoOperGravadas($detail['mtoOperGravadas'] ?? null)
                ->setMtoOperInafectas($detail['mtoOperInafectas'] ?? null)
                ->setMtoOperExoneradas($detail['mtoOperExoneradas'] ?? null)
                ->setMtoOperExportacion($detail['mtoOperExportacion'] ?? null)
                ->setMtoOperGratuitas($detail['mtoOperGratuitas'] ?? null)
                ->setMtoOtrosCargos($detail['mtoOtrosCargos'] ?? null)
                ->setMtoIGV($detail['mtoIGV'] ?? null)
                ->setMtoIvap($detail['mtoIvap'] ?? null)
                ->setMtoISC($detail['mtoISC'] ?? null)
                ->setMtoOtrosTributos($detail['mtoOtrosTributos'] ?? null)
                ->setMtoIcbper($detail['mtoIcbper'] ?? null);
        }   

        return $greenDetails;
    }

    public function getDocReferencia($docReferencia)
    {
        if (!$docReferencia) {
            return null;
        }

        return (new Document())
            ->setTipoDoc($docReferencia['tipoDoc'] ?? null)
            ->setNroDoc($docReferencia['nroDoc'] ?? null);
    }

    public function getPercepcion($percepcion)
    {
        if (!$percepcion) {
            return null;
        }

        return (new SummaryPerception())
            ->setCodReg($percepcion['codReg'] ?? null)
            ->setTasa($percepcion['tasa'] ?? null)
            ->setMtoBase($percepcion['mtoBase'] ?? null)
            ->setMto($percepcion['mto'] ?? null)
            ->setMtoTotal($percepcion['mtoTotal'] ?? null);
    }
}