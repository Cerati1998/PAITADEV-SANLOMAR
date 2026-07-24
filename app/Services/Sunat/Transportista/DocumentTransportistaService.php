<?php

namespace App\Services\Sunat\Transportista;

use App\Services\Sunat\DocumentService;
use App\Services\Sunat\Transportista\Model\DespatchTransportista;
use DateTime;
use Greenter\Model\Despatch\AdditionalDoc;
use Greenter\Model\Despatch\Transportist;

/**
 * Mapea el array de datos (request/Form Livewire) al modelo Greenter-compatible
 * propio de la Guía de Remisión Transportista.
 *
 * Reutiliza los mapeadores genéricos ya existentes en DocumentService
 * (empresa, cliente, dirección, vehículo, choferes, ítems) en vez de
 * duplicarlos, porque esos bloques son idénticos a los de la Guía de
 * Remisión Remitente — ver guia-transportista.md.
 */
class DocumentTransportistaService
{
    protected DocumentService $document;

    public function __construct(?DocumentService $document = null)
    {
        $this->document = $document ?? new DocumentService();
    }

    public function getDespatch($data): DespatchTransportista
    {
        return (new DespatchTransportista())
            ->setVersion($data['version'] ?? '2022')
            ->setTipoDoc($data['tipoDoc'] ?? '31') // Guia de Remision Transportista - Catalog. 01
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new DateTime($data['fechaEmision'] ?? null))
            ->setCompany($this->document->getCompany($data['company']))
            // Confirmado contra SUNAT beta (error XSD 0306): obligatorio, no opcional.
            ->setDestinatario($this->document->getClient($data['destinatario']))
            ->setRemitente($this->document->getClient($data['remitente']))
            ->setSubcontratador(!empty($data['subcontratador']['numDoc']) ? $this->document->getClient($data['subcontratador']) : null)
            ->setAddDocs($this->getAddDocs($data['documentoRelacionado'] ?? null))
            ->setEnvio($this->getEnvio($data['envio']))
            ->setDetails($this->document->getDespatchDetails($data['details']));
    }

    /**
     * Datos adicionales del documento emitido por el remitente que sustenta
     * el traslado (Catalogo 61: 09 = Guia Remitente, 92 = Cita/Orden Terminal
     * Portuario, 93-95 = ZED/ZOFRATACNA, etc.) — Anexo N.28, campo 16.
     */
    public function getAddDocs(?array $documentoRelacionado): ?array
    {
        if (empty($documentoRelacionado['nro'])) {
            return null;
        }

        return [
            (new AdditionalDoc())
                ->setTipo($documentoRelacionado['tipo'])
                ->setTipoDesc($documentoRelacionado['tipoDesc'] ?? null)
                ->setNro($documentoRelacionado['nro'])
                ->setEmisor($documentoRelacionado['emisor'] ?? null),
        ];
    }

    /**
     * Envio de la GRE-Transportista: siempre lleva vehiculo y choferes
     * (a diferencia de la GRE-Remitente, donde son condicionales al
     * modTraslado), porque el propio transportista es quien traslada.
     */
    public function getEnvio($envio)
    {
        $shipment = (new \Greenter\Model\Despatch\Shipment())
            ->setCodTraslado($envio['codTraslado']) // Cat.20
            ->setDesTraslado($envio['desTraslado'] ?? null)
            ->setModTraslado('01') // GRE-Transportista siempre es transporte publico
            ->setFecTraslado(new DateTime($envio['fecTraslado']))
            ->setPesoTotal($envio['pesoTotal'])
            ->setUndPesoTotal($envio['undPesoTotal'])
            ->setLlegada($this->document->getDirection($envio['llegada'], $envio['codTraslado']))
            ->setPartida($this->document->getDirection($envio['partida'], $envio['codTraslado']))
            ->setVehiculo($this->document->getVehiculo($envio['vehiculo']))
            ->setChoferes($this->document->getChoferes($envio['choferes']));

        // N. de inscripcion MTC/RNT propio del transportista (cac:CarrierParty).
        // Reutiliza el sub-objeto Transportist de Greenter solo para nroMtc
        // (confirmado contra XML real de produccion aceptado por SUNAT).
        if (!empty($envio['mtc'])) {
            $shipment->setTransportista((new Transportist())->setNroMtc($envio['mtc']));
        }

        return $shipment;
    }
}
