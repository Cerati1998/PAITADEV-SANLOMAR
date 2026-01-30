<?php

namespace App\Http\Controllers;

use App\Models\ArchingCash;
use App\Models\Bill;
use App\Models\CashDeposit;
use App\Models\Company;
use App\Models\Identity;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArchingCashController extends Controller
{
    public function get_cashes()
    {
        return view("cashes.index");
    }
    public function get_bills()
    {
        $identities = Identity::all();
        return view("bills.index", compact('identities'));

    }

  public function reportePDF($id)
{
    $cash = ArchingCash::with(['bills.provider', 'deposits', 'branch','closedBy'])
        ->findOrFail($id);

    $business = Company::first();

    // Obtener el logo
    $logoBase64 = null;
    if ($business->logo_path && Storage::exists($business->logo_path)) {
        $logoContent = Storage::get($business->logo_path);
        $mimeType = Storage::mimeType($business->logo_path);
        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
    }

    // Gastos
    $gastos = Bill::where('arching_cash_id', $cash->id)->get();

    // Ingresos (depósitos)
    $ingresos = CashDeposit::where('arching_cash_id', $cash->id)->get();

    // SUMAS
    $total_gastos = $gastos->sum('monto');
    $total_ingresos = $ingresos->sum('monto');

    $saldo_heredado = $cash->saldo_heredado ?? 0;

    $monto_final = 
          $saldo_heredado
        + $total_ingresos
        - $total_gastos;

    // Preparar datos para Blade
    $data = [
        'business'       => $business,
        'logoBase64'     => $logoBase64,
        'manager'        => $cash?->closedBy?->name ? strtoupper(string: $cash?->closedBy?->name) : '-',
        'cash'           => $cash,
        'gastos'         => $gastos,
        'ingresos'       => $ingresos,
        'total_gastos'   => $total_gastos,
        'total_ingresos' => $total_ingresos,
        'saldo_heredado' => $saldo_heredado,
        'monto_final'    => $monto_final,
    ];

    $html = view('pdf.summary-cash', $data)->render();

    // DomPDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'sans-serif');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->stream('arqueo_caja_' . $cash->id . '.pdf',['Attachment'=>false]);
}

}


