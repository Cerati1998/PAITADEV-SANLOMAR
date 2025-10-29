<?php

namespace App\Services\Sunat;

use App\Models\District;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Despatch\Direction;
use Greenter\Model\Despatch\Driver;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\Despatch\Transportist;
use Greenter\Model\Despatch\Vehicle;
use Greenter\Model\Sale\Charge;
use Greenter\Model\Sale\Cuota;
use Greenter\Model\Sale\Detraction;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\FormaPagos\FormaPagoCredito;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\Prepayment;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\SalePerception;

class DocumentService
{
    public function getInvoice($data)
    {
        return (new Invoice())
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoOperacion($data['tipoOperacion']) // Venta - Catalog. 51
            ->setTipoDoc($data['tipoDoc']) // Factura - Catalog. 01 
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new DateTime($data['fechaEmision']))
            ->setFecVencimiento(isset($data['fecVencimiento']) ? new DateTime($data['fecVencimiento']) : null)

            //Forma de Pago
            ->setFormaPago($this->getFormaPago($data['formaPago'] ?? null))
            ->setCuotas($this->getCuotas($data['cuotas'] ?? []))
            ->setTipoMoneda($data['tipoMoneda']) // Sol - Catalog. 02

            ->setGuias($data['guias'] ?? [])

            //Compañia
            ->setCompany($this->getCompany($data['company']))

            //Client
            ->setClient($this->getClient($data['client']))

            //Descuentos
            ->setDescuentos($this->getDescuentos($data['descuentos'] ?? []))
            ->setSumOtrosDescuentos($data['sumOtrosDescuentos']) // 0

            //MtoOper
            ->setMtoOperGravadas($data['mtoOperGravadas'] ?: null)
            ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?: null)
            ->setMtoOperInafectas($data['mtoOperInafectas'] ?: null)
            ->setMtoOperExportacion($data['mtoOperExportacion'] ?: null)
            ->setMtoOperGratuitas($data['mtoOperGratuitas'] ?: null)
            ->setMtoBaseIvap($data['mtoBaseIvap'] ?: null)
            ->setMtoBaseIsc($data['mtoBaseIsc'] ?: null)
            
            //Impuestos
            ->setMtoIGV($data['mtoIGV'] ?? 0) // 18
            ->setMtoIGVGratuitas($data['mtoIGVGratuitas'] ?? 0)
            ->setMtoIvap($data['mtoIvap'] ?? 0)
            ->setIcbper($data['icbper'] ?? 0)
            ->setMtoISC($data['mtoISC'] ?? 0)
            ->setTotalImpuestos($data['totalImpuestos'] ?? 0) // 18

            //Totales
            ->setValorVenta($data['valorVenta'] ?? 0 ?? 0) // 100
            ->setSubTotal($data['subTotal'] ?? 0) // 118
            ->setRedondeo($data['redondeo'] ?? 0)
            ->setMtoImpVenta($data['mtoImpVenta'] ?? 0)

            //Anticipos
            ->setAnticipos($this->getAnticipos($data['anticipos'] ?? []))
            ->setTotalAnticipos($data['totalAnticipos'] ?? 0)

            //Detracción
            ->setDetraccion($this->getDetraction($data['tipoOperacion'] == '1001' ? $data['detraccion'] : null))
            ->setPerception($this->getPerception($data['tipoOperacion'] == '2001' ? $data['perception'] : null))

            ->setDetails($this->getDetails($data['details'], $data['atributos'] ?? null))
            ->setLegends($this->getLegends($data['legends']));
    }

    public function getNote($data)
    {
        return (new Note())
            ->setUblVersion($data['ublVersion'] ?? '2.1')
            ->setTipoDoc($data['tipoDoc']) // Factura - Catalog. 01 
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new DateTime($data['fechaEmision'] ?? null))

            //Documento Afectado
            ->setTipDocAfectado($data['tipDocAfectado']) // Tipo Doc: Factura
            ->setNumDocfectado($data['numDocfectado']) // Factura: Serie-Correlativo

            ->setCodMotivo($data['codMotivo']) // Catalogo. 09
            ->setDesMotivo($data['desMotivo'])

            ->setGuias($data['guias'] ?? [])

            //Forma de Pago
            ->setTipoMoneda($data['tipoMoneda'] ?? 'PEN') // Sol - Catalog. 02

            //Compañia
            ->setCompany($this->getCompany($data['company']))

            //Emisor y Receptor
            ->setClient($this->getClient($data['client'] ?? []))

            //MtoOper
            ->setMtoOperGravadas($data['mtoOperGravadas'] ?: null)
            ->setMtoOperExoneradas($data['mtoOperExoneradas'] ?: null)
            ->setMtoOperInafectas($data['mtoOperInafectas'] ?: null)
            ->setMtoOperExportacion($data['mtoOperExportacion'] ?: null)
            ->setMtoOperGratuitas($data['mtoOperGratuitas'] ?: null)
            ->setMtoBaseIvap($data['mtoBaseIvap'] ?: null)
            ->setMtoBaseIsc($data['mtoBaseIsc'] ?: null)

            //Impuestos
            ->setMtoIGV($data['mtoIGV'] ?? 0) // 18
            ->setMtoIGVGratuitas($data['mtoIGVGratuitas'] ?? 0)
            ->setMtoIvap($data['mtoIvap'] ?? 0)
            ->setIcbper($data['icbper'] ?? 0)
            ->setMtoISC($data['mtoISC'] ?? 0)
            ->setTotalImpuestos($data['totalImpuestos'] ?? 0) // 18
            
            //Totales
            ->setValorVenta($data['valorVenta'] ?? 0) // 100
            ->setSubTotal($data['subTotal'] ?? 0) // 118
            ->setRedondeo($data['redondeo'] ?? 0)
            ->setMtoImpVenta($data['mtoImpVenta'] ?? 0)

            ->setDetails($this->getDetails($data['details']))
            ->setLegends($this->getLegends($data['legends']));
    }

    public function getDespatch($data)
    {
        return (new Despatch())
            ->setVersion($data['version'] ?? '2022')
            ->setTipoDoc($data['tipoDoc']) // Guia de Remision - Catalog. 09s
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlativo'])
            ->setFechaEmision(new DateTime($data['fechaEmision'] ?? null))
            ->setCompany($this->getCompany($data['company']))
            ->setDestinatario($this->getClient($data['destinatario']))
            ->setEnvio($this->getEnvio($data['envio']))
            ->setDetails($this->getDespatchDetails($data['details']));
    }

    public function getFormaPago($formaPago)
    {
        
        if (!$formaPago) {
            return null;
        }

        if ($formaPago['tipo'] == 'Contado') {
            return (new FormaPagoContado());
        }

        return new FormaPagoCredito($formaPago['monto'] ?? null);
    }

    public function getCuotas($cuotas)
    {
        $greenCuotas = [];

        foreach ($cuotas as $cuota) {
            $greenCuotas[] = (new Cuota())
                ->setMoneda($cuota['moneda'] ?? null)
                ->setMonto($cuota['monto'])
                ->setFechaPago(new DateTime($cuota['fechaPago']));
        }

        return $greenCuotas;
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

    public function getDescuentos($descuentos)
    {
        $charges = [];

        foreach ($descuentos as $descuento) {
            $charges[] = (new Charge())
                ->setCodTipo($descuento['codTipo']) // Catalog. 53
                ->setMontoBase($descuento['montoBase']) // 100
                ->setFactor($descuento['factor']) // 1
                ->setMonto($descuento['monto']); // 18
        }

        return $charges;
    }

    public function getAnticipos($anticipos)
    {
        $prepayments = [];

        foreach ($anticipos as $anticipo) {
            $prepayments[] = (new Prepayment())
                ->setTipoDocRel($anticipo['tipoDocRel']) // catalog. 12
                ->setNroDocRel($anticipo['nroDocRel'])
                ->setTotal($anticipo['total']);
        }

        return $prepayments;
    }

    public function getDetraction($detraccion)
    {
        if ($detraccion) {
            return (new Detraction())
                ->setMount($detraccion['mount'])
                ->setPercent($detraccion['percent'])
                ->setCodMedioPago($detraccion['codMedioPago'])
                ->setCtaBanco($detraccion['ctaBanco'])
                ->setCodBienDetraccion($detraccion['codBienDetraccion']);
        }

        return null;
    }

    public function getPerception($perception)
    {

        if ($perception) {
            return (new SalePerception())
                ->setCodReg($perception['codReg'])
                ->setPorcentaje($perception['porcentaje'])
                ->setMtoBase($perception['mtoBase'])
                ->setMto($perception['mto'])
                ->setMtoTotal($perception['mtoTotal']);
        }

        return null;

    }

    public function getDetails($details, $atributos = null)
    {
        $greenDetails = [];

        foreach ($details as $detail) {
            $greenDetails[] = (new SaleDetail())
                
                ->setCodProducto($detail['codProducto'])
                ->setCodProdSunat($detail['codProdSunat'] ?? null)
                ->setUnidad($detail['unidad'])
                ->setDescripcion($detail['descripcion'])
                ->setCantidad($detail['cantidad'])

                ->setMtoValorUnitario($detail['mtoValorUnitario'])
                ->setMtoValorGratuito($detail['mtoValorGratuito'] ?? null)

                ->setDescuentos($this->getDescuentos($detail['descuentos'] ?? []))

                ->setMtoValorVenta($detail['mtoValorVenta'])

                //Isc
                ->setMtoBaseIsc($detail['mtoBaseIsc'] ?? null)
                ->setTipSisIsc($detail['tipSisIsc'] ?? null)
                ->setPorcentajeIsc($detail['porcentajeIsc'] ?? null)
                ->setIsc($detail['isc'] ?? null)

                //Igv
                ->setMtoBaseIgv($detail['mtoBaseIgv'])
                ->setPorcentajeIgv($detail['porcentajeIgv'])
                ->setIgv($detail['igv'])

                //Icbper
                ->setFactorIcbper($detail['factorIcbper'] ?? null)
                ->setIcbper($detail['icbper'] ?? null)

                ->setTipAfeIgv($detail['tipAfeIgv'])

                ->setTotalImpuestos($detail['totalImpuestos'])
                ->setMtoPrecioUnitario($detail['mtoPrecioUnitario'])
                ->setAtributos($atributos);
        }

        return $greenDetails;
    }

    public function getLegends($legends)
    {
        $greenLegends = [];

        foreach ($legends as $legend) {
            $greenLegends[] = (new Legend())
                ->setCode($legend['code']) // 1000: Operacion Onerosa
                ->setValue($legend['value']);
        }

        return $greenLegends;
    }

    //Guías de Remisión
    public function getEnvio($envio)
    {

        $indicadores = $envio['indicadores'] ?? [];

        $shipment = (new Shipment())
            ->setCodTraslado($envio['codTraslado']) // Cat.20 - Venta
            ->setModTraslado($envio['modTraslado']) // Cat.18 - Transp. Publico
            ->setFecTraslado(new DateTime($envio['fecTraslado']))
            ->setPesoTotal($envio['pesoTotal'])
            ->setUndPesoTotal($envio['undPesoTotal'])

            ->setLlegada($this->getDirection($envio['llegada'], $envio['codTraslado']))
            ->setPartida($this->getDirection($envio['partida'], $envio['codTraslado']));

        if ($envio['modTraslado'] == '01') {
            $shipment->setTransportista($this->getTransportista($envio['transportista']));

            if (in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $indicadores)) {
                $key = array_search('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $indicadores);
                unset($indicadores[$key]);
            }
        }

        if ($envio['modTraslado'] == '02') {

            if (!in_array('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $indicadores)) {
                $shipment->setVehiculo($this->getVehiculo($envio['vehiculo']))
                    ->setChoferes($this->getChoferes($envio['choferes']));
            }
        }

        $shipment->setIndicadores($indicadores);

        return $shipment;
    }

    public function getDirection($data, $codTraslado)
    {

        $district = District::find($data['ubigueo']);
        $province = $district->province;
        $department = $province->department;

        $direccion = $department->name . ' - ' . $province->name . ' - ' . $district->name . ' - ' . $data['direccion'];

        $direction = new Direction($data['ubigueo'], $direccion);

        if ($codTraslado == '04') {
            $direction->setRuc($data['ruc'] ?? null)
                ->setCodLocal($data['codLocal'] ?? null);
        }

        return $direction;
    }

    public function getTransportista($transportista)
    {
        return (new Transportist())
            ->setTipoDoc($transportista['tipoDoc'])
            ->setNumDoc($transportista['numDoc'])
            ->setRznSocial($transportista['rznSocial'])
            ->setNroMtc($transportista['nroMtc']);
    }

    public function getVehiculo($vehiculo)
    {

        $secundarios = [];

        foreach ($vehiculo['secundarios'] ?? [] as $item) {
            $secundarios[] = (new Vehicle())
                ->setPlaca(strtoupper($item['placa']));
        }

        return (new Vehicle())
            ->setPlaca(strtoupper($vehiculo['placa']))
            ->setSecundarios($secundarios);
    }

    public function getChoferes($choferes)
    {
        $choferes = collect($choferes);

        $drivers = [];

        $drivers[] = (new Driver)
            ->setTipo('Principal')
            ->setTipoDoc($choferes->first()['tipoDoc'])
            ->setNroDoc($choferes->first()['nroDoc'])
            ->setLicencia($choferes->first()['licencia'])
            ->setNombres($choferes->first()['nombres'])
            ->setApellidos($choferes->first()['apellidos']);

        foreach ($choferes->slice(1) as $item) {
            $drivers[] = (new Driver)
                ->setTipo('Secundario')
                ->setTipoDoc($item['tipoDoc'])
                ->setNroDoc($item['nroDoc'])
                ->setLicencia($item['licencia'])
                ->setNombres($item['nombres'])
                ->setApellidos($item['apellidos']);
        }

        return $drivers;
    }

    public function getDespatchDetails($details)
    {
        $greenDetails = [];

        foreach ($details as $item) {
            $greenDetails[] = (new DespatchDetail)
                ->setCantidad($item['cantidad'])
                ->setUnidad($item['unidad'])
                ->setDescripcion($item['descripcion'])
                ->setCodigo($item['codigo']);
        }

        return $greenDetails;
    }
}