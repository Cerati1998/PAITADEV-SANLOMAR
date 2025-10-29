<table style="width: 100%; border-bottom: 3px solid #000; margin-bottom: 5px;">
    <tr>
        <td style="width: 15%; text-align: center; vertical-align: top;">
            @if(isset($logo) && file_exists(public_path($logo)))
                <img src="{{ public_path($logo) }}" style="max-width: 60px; max-height: 45px;">
            @else
                <div style="width: 60px; height: 40px; border: 1px solid #ccc; display: inline-block; line-height: 40px; font-size: 7px;">LOGO</div>
            @endif
        </td>
        
        <td style="width: 60%; vertical-align: top; padding-left: 5px;">
            <div style="font-size: 14px; font-weight: bold;">{{ $companyName ?? 'MACROMAR LOGISTICS SAC' }}</div>
            <div style="font-size: 10px; font-weight: bold;">{{ $documentTitle ?? 'EQUIPMENT INTERCHANGE RECEIPT (EIR - GATE IN)' }}</div>
        </td>
        
        <td style="width: 25%; vertical-align: top;">
            <table style="width: 100%; border: 2px solid #000; border-collapse: collapse;">
                <tr>
                    <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; background: #f0f0f0; width: 35%;">Fecha:</td>
                    <td style="padding: 2px 4px; border: 1px solid #000; width: 65%;">{{ $fechaGeneracion ?? '06-09-2025' }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; background: #f0f0f0;">Ver:</td>
                    <td style="padding: 2px 4px; border: 1px solid #000;">{{ $version ?? 'SL-01' }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px 4px; border: 1px solid #000; font-weight: bold; background: #f0f0f0;">Año:</td>
                    <td style="padding: 2px 4px; border: 1px solid #000;">{{ $year ?? '2025' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>