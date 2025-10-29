<?php

namespace App\Http\Controllers\operations;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Identity;
use App\Models\works\WorkOrderLabor;
use App\Services\WorkOrders\WorkLaborPhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    public function manage_box()
    {
        $branches = Branch::where('company_id', session('company')->id)->get();
        //$identities = Identity::whereIn('id', [1, 2])->get();
        $identities = Identity::find([1, 2]);
        $cargos = ['Reparador', 'Inspector', 'Asistente', 'Despachador', 'Checker', 'Soldador', 'Reefer', 'Otro'];
        return view('works.orders.index', compact('branches', 'identities', 'cargos'));
    }
    public function manage_mach() {}

    public function create_box() {}

    /**
     * Subir foto de labor
     */
    public function upload_labor_photos(Request $request, WorkLaborPhotoService $photoService)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'labor_id' => 'required|exists:work_order_labors,id',
        ]);

        $labor = WorkOrderLabor::with('workOrder')->findOrFail($request->labor_id);

        // Validar que la orden esté en borrador
        if ($labor->workOrder->status !== 'borrador') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes agregar fotos, la orden de trabajo ya se encuentra cerrada'
            ], 403);
        }

        try {
            $photoService->validateImage($request->file('photo'));

            $url = $photoService->saveLaborPhoto($labor, $request->file('photo'));

            return response()->json([
                'success' => true,
                'url' => $url,
                'photos' => $labor->fresh()->photos->map(function ($photo) use ($photoService) {
                    return [
                        'id' => $photo->id,
                        'url' => $photoService->getPhotoUrl($photo->path),
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error subiendo foto de labor: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
