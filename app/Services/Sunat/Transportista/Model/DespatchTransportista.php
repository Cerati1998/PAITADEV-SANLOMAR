<?php

namespace App\Services\Sunat\Transportista\Model;

use DateTimeInterface;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\AdditionalDoc;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\DocumentInterface;

/**
 * Modelo de documento "Guía de Remisión Transportista" (tipoDoc 31), compatible
 * con Greenter (implementa DocumentInterface) pero NO provisto por la librería.
 *
 * Greenter 5.1 solo modela/renderiza la estructura de la Guía de Remisión
 * Remitente (tipoDoc 09) — ver guia-transportista.md, sección "Por qué un
 * modelo propio". Este modelo agrega los bloques exigidos por el Anexo N.° 14-b
 * de la R.S. 000108-2026/SUNAT que no existen en Greenter\Model\Despatch\Despatch:
 * remitente (cac:DespatchParty) y subcontratador (cac:Consignment/cac:LogisticsOperatorParty).
 */
class DespatchTransportista implements DocumentInterface
{
    private ?string $version = '2022';
    private ?string $tipoDoc = '31';
    private ?string $serie = null;
    private ?string $correlativo = null;
    private ?string $observacion = null;
    private ?DateTimeInterface $fechaEmision = null;

    private ?Company $company = null;

    /** Destinatario final de los bienes (opcional según el traslado). */
    private ?Client $destinatario = null;

    /** Remitente que entrega los bienes al transportista (obligatorio). */
    private ?Client $remitente = null;

    /** Empresa a la que el transportista subcontrata, si aplica. */
    private ?Client $subcontratador = null;

    private ?Shipment $envio = null;

    /** @var AdditionalDoc[] */
    private ?array $addDocs = null;

    /** @var DespatchDetail[] */
    private ?array $details = null;

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getTipoDoc(): ?string
    {
        return $this->tipoDoc;
    }

    public function setTipoDoc(?string $tipoDoc): self
    {
        $this->tipoDoc = $tipoDoc;
        return $this;
    }

    public function getSerie(): ?string
    {
        return $this->serie;
    }

    public function setSerie(?string $serie): self
    {
        $this->serie = $serie;
        return $this;
    }

    public function getCorrelativo(): ?string
    {
        return $this->correlativo;
    }

    public function setCorrelativo(?string $correlativo): self
    {
        $this->correlativo = $correlativo;
        return $this;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function setObservacion(?string $observacion): self
    {
        $this->observacion = $observacion;
        return $this;
    }

    public function getFechaEmision(): ?DateTimeInterface
    {
        return $this->fechaEmision;
    }

    public function setFechaEmision(?DateTimeInterface $fechaEmision): self
    {
        $this->fechaEmision = $fechaEmision;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getDestinatario(): ?Client
    {
        return $this->destinatario;
    }

    public function setDestinatario(?Client $destinatario): self
    {
        $this->destinatario = $destinatario;
        return $this;
    }

    public function getRemitente(): ?Client
    {
        return $this->remitente;
    }

    public function setRemitente(?Client $remitente): self
    {
        $this->remitente = $remitente;
        return $this;
    }

    public function getSubcontratador(): ?Client
    {
        return $this->subcontratador;
    }

    public function setSubcontratador(?Client $subcontratador): self
    {
        $this->subcontratador = $subcontratador;
        return $this;
    }

    public function getEnvio(): ?Shipment
    {
        return $this->envio;
    }

    public function setEnvio(?Shipment $envio): self
    {
        $this->envio = $envio;
        return $this;
    }

    /** @return AdditionalDoc[]|null */
    public function getAddDocs(): ?array
    {
        return $this->addDocs;
    }

    /** @param AdditionalDoc[]|null $addDocs */
    public function setAddDocs(?array $addDocs): self
    {
        $this->addDocs = $addDocs;
        return $this;
    }

    /** @return DespatchDetail[]|null */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /** @param DespatchDetail[]|null $details */
    public function setDetails(?array $details): self
    {
        $this->details = $details;
        return $this;
    }

    /**
     * Nombre de archivo sin extensión: RUC-tipoDoc-serie-correlativo.
     * Mismo formato que Greenter\Model\Despatch\Despatch::getName().
     */
    public function getName(): string
    {
        $parts = [
            $this->company->getRuc(),
            $this->getTipoDoc(),
            $this->getSerie(),
            $this->getCorrelativo(),
        ];

        return join('-', $parts);
    }
}
