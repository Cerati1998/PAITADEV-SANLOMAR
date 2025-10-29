<?php

namespace App\Traits\Sunat;

use Luecano\NumeroALetras\NumeroALetras;

trait DataTrait
{
    public function getData($data)
    {
        $this->getTotales($data);
        $this->getLegends($data);
        return $data;
    }

    public function getTotales(&$data)
    {
        $details = collect($data['details']);
        $anticipos = collect($data['anticipos'] ?? []);
        $descuentos = collect($data['descuentos'] ?? []);

        $data['sumOtrosDescuentos'] = $descuentos->where('codTipo', '03')->sum('monto')
            + $details->flatMap(function ($detail) {
                return $detail['descuentos'] ?? [];
            })
            ->where('codTipo', '01')
            ->sum('monto');

        $data['totalAnticipos'] = $anticipos->sum('total');

        $data['mtoOperGravadas'] = $details->where('tipAfeIgv', '10')->sum('mtoValorVenta') - $descuentos->whereIn('codTipo', ['02', '04'])->sum('monto');
        $data['mtoOperExoneradas'] = $details->where('tipAfeIgv', '20')->sum('mtoValorVenta');
        $data['mtoOperInafectas'] = $details->where('tipAfeIgv', '30')->sum('mtoValorVenta');
        $data['mtoOperExportacion'] = $details->where('tipAfeIgv', '40')->sum('mtoValorVenta');
        $data['mtoOperGratuitas'] = $details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('mtoValorVenta');
        $data['mtoBaseIvap'] = $details->where('tipAfeIgv', '17')->sum('mtoValorVenta');
        $data['mtoBaseIsc'] = $details->sum('mtoBaseIsc');

        $data['mtoIGV'] = $details->whereIn('tipAfeIgv', ['10', '20', '30', '40'])->sum('igv') - $descuentos->whereIn('codTipo', ['02', '04'])->sum('monto') * 0.18;
        $data['mtoIGVGratuitas'] = $details->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('igv');
        $data['mtoIvap'] = $details->where('tipAfeIgv', '17')->sum('igv');
        $data['icbper'] = $details->sum('icbper');
        $data['mtoISC'] = $details->sum('isc');

        $data['totalImpuestos'] = $data['mtoIGV'] + $data['icbper'] + $data['mtoIvap'] + $data['mtoISC'];

        $data['valorVenta'] = $details->whereIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->sum('mtoValorVenta') - $descuentos->where('codTipo', '02')->sum('monto');
        $data['subTotal'] = $data['valorVenta'] + $data['totalImpuestos'] + $descuentos->where('codTipo', '04')->sum('monto') * 0.18;
        $mtoImpVenta = $data['subTotal'] - $data['sumOtrosDescuentos'] - $data['totalAnticipos'];
        $data['mtoImpVenta'] = floor($mtoImpVenta * 10) / 10;
        $data['redondeo'] =  $mtoImpVenta - $data['mtoImpVenta'];
    }

    public function getLegends(&$data)
    {
        $formatter = new NumeroALetras();

        $legends = [];

        if (collect($data['details'])->whereIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->count()) {
         
            $currency = match ($data['tipoMoneda']) {
                'PEN' => 'SOLES',
                'USD' => 'DÓLARES AMERICANOS',
                default => $data['tipoMoneda'],
            };

            $legends[] = [
                'code' => '1000',
                'value' => $formatter->toInvoice($data['mtoImpVenta'], 2, $currency)
            ];

        }

        if (collect($data['details'])->whereNotIn('tipAfeIgv', ['10', '17', '20', '30', '40'])->count()) {
            $legends[] = [
                'code' => '1002',
                'value' => 'TRANSFERENCIA GRATUITA DE UN BIEN Y/O SERVICIO PRESTADO GRATUITAMENTE'
            ];
        }

        if (collect($data['details'])->where('tipAfeIgv', '17')->count()) {
            $legends[] = [
                'code' => '2007',
                'value' => 'Operación sujeta al IVAP'
            ];
        }

        if (in_array($data['tipoDoc'], ['01','03'])) {   
        
            if ($data['tipoOperacion'] == '1001') {
                $legends[] = [
                    'code' => '2006',
                    'value' => 'Operación sujeta a detracción'
                ];
            }

            if ($data['tipoOperacion'] == '2001') {
                $legends[] = [
                    'code' => '2000',
                    'value' => 'COMPROBANTE DE PERCEPCIÓN'
                ];
            }

        }

        $data['legends'] = $legends;
    }
}
