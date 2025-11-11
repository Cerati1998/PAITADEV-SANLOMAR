<?php

namespace App\Http\Controllers\operations;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Identity;
use App\Models\works\WorkOrder;
use App\Models\works\WorkOrderLabor;
use App\Models\works\WorkOrderLaborImage;
use App\Services\WorkOrders\WorkLaborPhotoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    public function download_photos($workOrderId)
    {
        try {
            //Log::info('Iniciando descarga de fotos', ['work_order_id' => $workOrderId]);

            $workOrder = WorkOrder::findOrFail($workOrderId);

            // Obtener todas las fotos de todos los labors de esta orden de trabajo
            $photos = WorkOrderLaborImage::whereHas('workOrderLabor', function ($query) use ($workOrderId) {
                $query->where('work_order_id', $workOrderId);
            })->pluck('path')->toArray();

            //Log::info('Fotos encontradas', ['count' => count($photos)]);

            if (empty($photos)) {
                throw new \RuntimeException('No hay fotos disponibles para descargar');
            }

            $zipFileName = "work_order_{$workOrderId}_photos.zip";
            $photoService = app(WorkLaborPhotoService::class);
            $zipPath = $photoService->createZipFromPaths($photos, $zipFileName);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\RuntimeException $e) {
            Log::error('Error RuntimeException downloading work order photos', [
                'work_order_id' => $workOrderId,
                'error' => $e->getMessage()
            ]);

            session()->flash('error', $e->getMessage());
            return back();
        } catch (\Exception $e) {
            Log::error('Error Exception downloading work order photos', [
                'work_order_id' => $workOrderId,
                'error' => $e->getMessage()
            ]);

            session()->flash('error', 'No se pudo descargar las fotos');
            return back();
        }
    }
    public function download_photo($photoId)
    {
        try {
            $photo = WorkOrderLaborImage::findOrFail($photoId);

            $relativePath = parse_url($photo->path, PHP_URL_PATH);
            $relativePath = ltrim($relativePath, '/');
            $disk = 'do';

            // Obtener el contenido del archivo
            $file = Storage::disk($disk)->get($relativePath);
            $fileName = basename($relativePath);
            $mimeType = Storage::disk($disk)->mimeType($relativePath);

            // Forzar descarga con headers correctos
            return response($file, 200)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error downloading photo', [
                'photo_id' => $photoId,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'No se pudo descargar la foto');
        }
    }
}
