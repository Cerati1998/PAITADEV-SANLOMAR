<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Arqueo de Caja</title>
    <style>
        body {
            font-family: sans-serif;
        }

        #header-container {
            position: relative;
            margin-bottom: 20px;
            min-height: 80px;
        }

        #logo-container {
            position: absolute;
            left: 0;
            top: 0;
        }

        #logo-container img {
            max-width: 120px;
            max-height: 80px;
            object-fit: contain;
        }

        #cabecera {
            text-align: center;
            padding-top: 40px;
        }

        #cabecera h3 {
            text-decoration: underline;
            margin: 0;
        }

        .border {
            border: 1px solid #ccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 4px;
            font-size: 12px;
        }

        .header th {
            text-align: right;
            padding-right: 10px;
        }

        .text-right {
            text-align: right;
        }

        .text-danger {
            color: red;
        }
    </style>
</head>

<body>

    <div id="header-container">
        <div id="logo-container">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo Empresa">
            @else
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjgwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMjAiIGhlaWdodD0iODAiIGZpbGw9IiNlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+TE9HTzwvdGV4dD48L3N2Zz4=" alt="Logo Placeholder">
            @endif
        </div>
        <div id="cabecera">
            <h3>REPORTE DE LIQUIDACIÓN FONDO FIJO</h3>
        </div>
    </div>

    <table class="header">
        <tr>
            <th>EMPRESA</th>
            <td>{{ $business->razonSocial }}</td>
        </tr>
        <tr>
            <th>CAJA Nº</th>
            <td>{{ $cash->correlativo }}</td>
        </tr>
        <tr>
            <th>RESPONSABLE</th>
            <td>{{ $manager }}</td>
        </tr>
        <tr>
            <th>FECHA APERTURA</th>
            <td>{{ $cash->fecha_inicio->format('d-m-Y H:i') }}</td>
        </tr>
        <tr>
            <th>MONTO INICIAL</th>
            <td>S/ {{ number_format($cash->monto_inicial, 2) }}</td>
        </tr>
    </table>

    <br>

    <h4>DETALLE DE INGRESOS</h4>
    <table class="border">
        <thead>
            <tr class="border">
                <th class="border">Fecha</th>
                <th class="border">Tipo</th>
                <th class="border">Método</th>
                <th class="border">Movimiento</th>
                <th class="border">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border">-</td>
                <td class="border">Saldo Anterior</td>
                <td class="border">-</td>
                <td class="border">-</td>
                <td class="border">S/ {{ number_format($saldo_heredado, 2) }}</td>
            </tr>
            @foreach ($ingresos as $i)
                <tr>
                    <td class="border">{{ $i->fecha_deposito->format('d-m-Y') }}</td>
                    <td class="border">{{ ucfirst($i->tipo_deposito) }}</td>
                    <td class="border">{{ $i->metodo }}</td>
                    <td class="border">{{ $i->numero_operacion ?? '-' }}</td>
                    <td class="border">S/ {{ number_format($i->monto, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="text-right border"><strong>Total ingresos:</strong></td>
                <td class="border"><strong>S/ {{ number_format($total_ingresos + $saldo_heredado, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <br>

    <h4>DETALLE DE GASTOS</h4>
    <table class="border">
        <thead>
            <tr class="border">
                <th class="border">Fecha</th>
                <th class="border">Proveedor</th>
                <th class="border">Detalle</th>
                <th class="border">Tipo</th>
                <th class="border">Comprobante</th>
                <th class="border">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($gastos as $g)
                <tr>
                    <td class="border">{{ $g->fecha_emision->format('d-m-Y') }}</td>
                    <td class="border">{{ $g->provider->rznSocial ?? '-' }}</td>
                    <td class="border">{{ $g->detalle }}</td>
                    <td class="border">{{ $g->tipo_comprobante ?? 'Otros' }}</td>
                    <td class="border">
                        @if ($g->numero_comprobante)
                            {{ basename($g->numero_comprobante) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="border text-danger">-S/ {{ number_format($g->monto, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" class="text-right border"><strong>Total gastos:</strong></td>
                <td class="border text-danger"><strong>-S/ {{ number_format($total_gastos, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <br>

    <h3>TOTAL FINAL</h3>
    <table>
        <tr>
            <th>Total ingresos</th>
            <td>S/ {{ number_format($total_ingresos + $saldo_heredado, 2) }}</td>
        </tr>
        <tr>
            <th>Total gastos</th>
            <td>-S/ {{ number_format($total_gastos, 2) }}</td>
        </tr>
        <tr>
            <th>Monto final</th>
            <td><strong>S/ {{ number_format($monto_final, 2) }}</strong></td>
        </tr>
    </table>

</body>

</html>