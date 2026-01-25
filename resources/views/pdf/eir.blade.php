<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 5px;
            color: #222;
        }

        h1,
        h2,
        h3,
        h4 {
            margin: 0;
            padding: 0;
        }

        /* HEADER */
        .header {
            border-bottom: 2px solid #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .header-info {
            border: 1px solid #444;
            width: 100%;
            border-collapse: collapse;
        }

        .header-info td {
            border: 1px solid #444;
            padding: 3px 6px;
            font-size: 9px;
        }

        .header-label {
            background: #efefef;
            font-weight: bold;
            text-align: right;
            width: 40%;
        }

        /* TABLES GENERAL */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        td,
        th {
            border: 1px solid #666;
            padding: 4px 6px;
        }

        th {
            background: #eaeaea;
            font-size: 8px;
            text-transform: uppercase;
        }

        td .eir-title {
            display: flex;
            text-align: center;
            justify-content: center;
            font-weight: bold;
            margin-top: 12px;
        }

        td .container-img {
            display: flex;
            text-align: center;
            margin-top: 12px;
        }

        td .no-content {
            color: red;
            font-size: 9px;
            display: flex;
            text-align: center;
            font-weight: bold;
            margin: 15px 15px;
        }

        /* SECTIONS */
        .section {
            border: 1px solid #555;
            background: #fafafa;
        }

        .section .label {
            background: #e8e8e8;
            font-weight: bold;
        }

        /* CONTAINER INFO */
        .field-label {
            display: block;
            font-size: 8px;
            color: #555;
        }

        .field-value {
            display: block;
            font-size: 9px;
            font-weight: bold;
            color: #111;
        }

        .container-id {
            font-size: 11px !important;
        }

        /* CHECKBOXES */
        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            text-align: center;
            line-height: 8px;
            font-size: 8px;
            margin-right: 4px;
        }

        /* DAMAGE TABLE */
        .damage-table th {
            background: #dcdcdc;
            font-weight: bold;
        }

        .damage-table td {
            font-size: 8.5px;
        }

        /* FOOTER */
        .footer {
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 5px;
        }

        .footer-table td {
            font-size: 8px;
        }

        .footer-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #666;
            background: white;
            padding: 5px 0;
            border-top: 1px solid #ddd;
            z-index: 1000;
        }

        /* HELPERS */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .bg-red-100 {
            --tw-bg-opacity: 1;
            background-color: rgb(254 226 226 / var(--tw-bg-opacity))
                /* #fee2e2 */
            ;
        }

        .text-gray-600 {
            --tw-text-opacity: 1;
            color: rgb(75 85 99 / var(--tw-text-opacity))
                /* #4b5563 */
            ;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 20%;">
                    @if (isset($headerData['logo']))
                        <div class="container-img">
                            <img src="{{ $headerData['logo'] }}" style="max-width: 90px; height:auto;">
                        </div>
                    @else
                        <div
                            style="width: 65px; height: 45px; border: 1px solid #ccc; display: inline-block; line-height: 45px; font-size: 7px; color: #666;">
                            LOGO</div>
                    @endif
                </td>

                <td style="width: 60%;">
                    <div class="eir-title">
                        <div style="font-size: 13px; margin-bottom: 2px;">{{ $headerData['documentTitle'] }}</div>
                        <div style="font-size: 10px; ">{{ $headerData['companyName'] }}</div>
                    </div>
                </td>

                <td style="width: 25%; vertical-align: top;">
                    <table class="header-info">
                        <tr>
                            <td class="header-label">Fecha:</td>
                            <td>{{ $headerData['fechaGeneracion'] }}</td>
                        </tr>
                        <tr>
                            <td class="header-label">Ver:</td>
                            <td>{{ $headerData['version'] }}</td>
                        </tr>
                        <tr>
                            <td class="header-label">Año:</td>
                            <td>{{ $headerData['year'] }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- FECHA Y HORA DE INICIO -->
    <table class="section">
        <tr>
            <td class="label" style="width: 40%;">FECHA Y HORA DE INICIO DE INSPECCION:</td>
            <td style="width: 35%;">{{ $fechaHoraInicio }}</td>
            <td style="width: 25%;" class="text-center text-bold">EIR N°{{ $numEir }}</td>
        </tr>
        <tr>
            <td class="label" style="width: 40%;">INSPECTOR IICL:</td>
            <td style="width: 35%;">{{ $inspector }}</td>
            <td style="width: 25%;" class="text-center text-bold">{{ $codigoInspector }}</td>
        </tr>
    </table>

    <!-- INFORMACIÓN DEL CONTENEDOR -->
    <table class="container-table">
        <tr>
            <td style="width: 25%;" colspan="2">
                <span class="field-label">CONTENEDOR ID:</span>
                <span class="field-value container-id">{{ $contenedorId }}</span>
            </td>
            <td style="width: 17%;">
                <span class="field-label">ISO</span>
                <span class="field-value">{{ $iso }}</span>
            </td>
            <td style="width: 18%;">
                <span class="field-label">TIPO:</span>
                <span class="field-value">{{ $tipo }}</span>
            </td>
            <td>
                <span class="field-label">FECHA DE FABRICACION:</span>
                <span class="field-value">{{ $fechaFabricacion }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="field-label">CLASE:</span>
                <span class="field-value">{{ $containerClassLine }}</span>
            </td>
            <td>
                <span class="field-label">TARE:</span>
                <span class="field-value">{{ $tare }}</span>
            </td>
            <td>
                <span class="field-label">PAYLOAD:</span>
                <span class="field-value">{{ $payload }}</span>
            </td>
            <td>
                <span class="field-label">GROSS WEIGHT:</span>
                <span class="field-value">{{ (int) $maxGross }}</span>
            </td>
            <td>
                <span class="field-label">HEIGHT OF CARGO:</span>
                <span class="field-value">{{ (int) $maxHeight }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="field-label">LÍNEA</span>
                <span class="field-value">{{ $linea }}</span>
            </td>
            <td colspan="2">
                <span class="field-label">NAVE</span>
                <span class="field-value">{{ $nave }}</span>
            </td>
            <td>
                <span class="field-label">VIAJE</span>
                <span class="field-value">{{ $viaje }}</span>
            </td>
        </tr>
    </table>

    <!-- LAVADO -->
    <table class="washing-table">
        <tr>
            <td style="width: 25%;">
                <span class="checkbox">{{ $lavadoNormal ? 'X' : '' }}</span>
                <span class="text-bold">LAVADO NORMAL</span>
            </td>
            <td style="width: 25%;">
                <span class="checkbox">{{ $lavadoQuimico ? 'X' : '' }}</span>
                <span class="text-bold">LAVADO QUIMICO</span>
            </td>
            <td style="width: 25%;">
                <span class="checkbox">{{ $lavadoEspecial ? 'X' : '' }}</span>
                <span class="text-bold">WX (LAVADO ESPECIAL)</span>
            </td>
            <td style="width: 25%;">
                <span class="checkbox">{{ $lavadoOxalico ? 'X' : '' }}</span>
                <span class="text-bold">LAVADO OXALICO</span>
            </td>
        </tr>
        <tr>
            <td class="text-center" style="width: 10%;">{{ $lavadoNormal ? 'Si' : 'No' }}</td>
            <td style="width: 25%;">
                <span>{{ $motivoLavadoQuimico }}</span>
            </td>
            <td style="width: 40%;">
                <span>{{ $motivoLavadoEspecial }}</span>
            </td>
            <td style="width: 25%;">
                <span>-</span>
            </td>
        </tr>
    </table>

    <!-- DAÑOS -->
    <table class="damage-table">
        <thead>
            <tr>
                <th style="width: 7%;">UBICACION</th>
                <th style="width: 7%;">DAÑO</th>
                <th style="width: 9%;">COMPONENTE</th>
                <th style="width: 7%;">MÉTODO</th>
                <th style="width: 4%;">CANT</th>
                <th colspan="3" style="width: 15%;">MEDIDA (CM)</th>
                <th style="width: 25%;">COMENTARIO</th>
                <th style="width: 4%;">Foto</th>
                <th style="width: 4%;">Foto</th>
                <th style="width: 4%;">Foto</th>
                <th style="width: 4%;">Foto</th>
            </tr>
            <tr>
                <th colspan="5"></th>
                <th style="width: 5%;">Largo</th>
                <th style="width: 5%;">Ancho</th>
                <th style="width: 5%;">Altura</th>
                <th colspan="5"></th>
            </tr>
        </thead>
        <tbody>
            @if (!empty($damages))
                @foreach ($damages as $damage)
                    {{ $class = !$damage['approved'] ? 'bg-red-100 text-gray-600' : '' }}
                    <tr>
                        <td class="ubicacion-cell {{ $class }}">{{ $damage['ubicacion'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['dano'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['componente'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['metodo'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['cantidad'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['largo'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['ancho'] }}</td>
                        <td class="text-center {{ $class }}">{{ $damage['altura'] }}</td>
                        <td class="text-left {{ $class }}">{{ $damage['comentario'] }}</td>
                        <td>
                            @if ($damage['foto1'])
                                <a href="{{ $damage['foto1'] }}" style="text-decoration: none; display: inline-block"
                                    target="_blank">
                                    <img src="{{ $damage['foto1'] }}"
                                        style="max-width: 40px; max-height: 40px; object-fit: cover;">
                                </a>
                            @endif
                        </td>
                        <td>
                            @if ($damage['foto2'])
                                <a href="{{ $damage['foto2'] }}" style="text-decoration: none; display: inline-block"
                                    target="_blank">
                                    <img src="{{ $damage['foto2'] }}"
                                        style="max-width: 40px; max-height: 40px; object-fit: cover;">
                                </a>
                            @endif
                        </td>
                        <td>
                            @if ($damage['foto3'])
                                <a href="{{ $damage['foto3'] }}" style="text-decoration: none; display: inline-block"
                                    target="_blank">
                                    <img src="{{ $damage['foto3'] }}"
                                        style="max-width: 40px; max-height: 40px; object-fit: cover;">
                                </a>
                            @endif
                        </td>
                        <td>
                            @if ($damage['foto4'])
                                <a href="{{ $damage['foto4'] }}" style="text-decoration: none; display: inline-block"
                                    target="_blank">
                                    <img src="{{ $damage['foto4'] }}"
                                        style="max-width: 40px; max-height: 40px; object-fit: cover;">
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="13">
                        <span class="no-content">
                            No se han encontrado detalles en la Inspección</span>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- CRITERIOS -->
    <table class="section">
        <tr>
            <td class="label" style="width: 30%;">CRITERIOS DE INSPECCION</td>
            <td>
                <span class="checkbox">{{ $criterioIICL ? 'X' : '' }}</span>
                <span class="text-bold">IICL</span>
                &nbsp;&nbsp;
                <span class="checkbox">{{ $criterioBASC ? 'X' : '' }}</span>
                <span class="text-bold">BASC (7 PUNTOS)</span>
                &nbsp;&nbsp;
                <span class="checkbox">{{ $criterioCargoWorthy ? 'X' : '' }}</span>
                <span class="text-bold">CARGO WORTHY</span>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="section">
            <tr>
                <td class="label">FECHA Y HORA DE TÉRMINO DE INSPECCION</td>
                <td>{{ $footerData['fechaHoraTermino'] }}</td>
            </tr>
            <tr>
                <td class="label">REAPERTURA DE INSPECCION</td>
                <td>{{ $footerData['fechaHoraReapertura'] }}</td>
            </tr>
        </table>

        <div class="footer-fixed">
            © CONTENEDOR INSPECCIONADO BAJO LOS ESTÁNDARES IICL, BASC (07 PUNTOS STANDARD V. 6.01 - 2.2)
        </div>
    </div>

</body>

</html>
