<?php

namespace App\Http\Controllers\operations;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ContainerOperationalTrace;
use App\Models\ContainerType;
use App\Models\Damage;
use App\Models\Document;
use App\Models\Identity;
use App\Models\inspections\EirInspection;
use App\Models\inspections\EirInspectionDetail;
use App\Models\inspections\RoofEvidence;
use App\Models\Location;
use App\Models\Method;
use App\Models\ReeferMachine;
use App\Models\ReeferTechnology;
use App\Services\inspections\EirPhotoService;
use App\Services\inspections\RoofEvidencePhotoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InspectionController extends Controller
{
    public function manage_eir()
    {
        return view('inspections.eir.index');
    }
    public function manage_leak()
    {
        return view('inspections.leak.index');
    }
    public function create_eir($trace = null)
    {
        if ($trace !== null) {
            $traceId = filter_var($trace, FILTER_VALIDATE_INT);

            if ($traceId === false) {
                session()->flash('swal', [
                    'icon' => 'error',
                    'title' => 'Error!',
                    'text' => 'La traza indicada no es válida.',
                ]);

                return redirect()->route('containers.traces');
            }

            $containerTrace = ContainerOperationalTrace::whereKey($traceId)->forBranch()->first();

            if (!$containerTrace) {
                session()->flash('swal', [
                    'icon' => 'error',
                    'title' => 'Error!',
                    'text' => 'No se encontró el contenedor en tu sucursal.',
                ]);

                return redirect()->route('containers.traces');
            }

            $existing = EirInspection::existingForTrace($traceId);

            if ($existing) {
                session()->flash('swal', [
                    'icon' => 'info',
                    'title' => 'EIR ya generado',
                    'text' => "Este contenedor ya tiene la inspección {$existing->documentLabel()}. No se puede generar otro EIR para él.",
                ]);

                if (auth()->user()->can('inspections.eir.edit')) {
                    return redirect()->route('inspections.eir.edit', $existing->id);
                }

                return redirect()->route('containers.traces');
            }
        }

        $catalogs = [
            'locations' => Location::all(),
            'damages' => Damage::all(),
            'components' => Component::all(),
            'methods' => Method::all(),
            'container_types' => ContainerType::all(),
        ];

        return view('inspections.eir.create', compact('trace', 'catalogs'));
    }
    public function edit_eir(EirInspection $eir)
    {
        $catalogs = [
            /* 'locations' => Location::all(),
            'damages' => Damage::all(),
            'components' => Component::all(),
            'methods' => Method::all(), */
            'container_types' => ContainerType::all(),
        ];

        return view('inspections.eir.edit', [
            'catalogs' => $catalogs,
            'eir_id' => $eir->id,
        ]);
    }

    /**
     * Subir foto de cabecera (posiciones 1-4)
     */
    public function uploadHeaderPhoto(Request $request, EirPhotoService $photoService)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'position' => 'required|integer|min:1|max:4',
            'eir_id' => 'required|exists:eir_inspections,id',
        ]);

        $eir = EirInspection::findOrFail($request->eir_id);

        if ($eir->status !== 'borrador') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes actualizar el registro, el EIR ya se encuentra cerrado'
            ], 403);
        }

        try {
            // Eliminar foto anterior si existe
            $oldPhotoField = "path_photo_{$request->position}";
            $oldPhotoPath = $eir->$oldPhotoField;

            if ($oldPhotoPath) {
                try {
                    if (Storage::disk('do')->exists($oldPhotoPath)) {
                        Storage::disk('do')->delete($oldPhotoPath);
                    }
                } catch (\Exception $e) {
                    Log::warning('Error eliminando foto anterior: ' . $e->getMessage());
                }
            }

            // Guardar nueva foto
            $path = $photoService->saveHeaderPhoto(
                $eir,
                $request->file('photo'),
                $request->position
            );

            return response()->json([
                'success' => true,
                'url' => $photoService->getPhotoUrl($path),
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadRoofEvidencePhoto(Request $request, RoofEvidencePhotoService $roofEvidencePhotoService)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'eir_id' => 'required|integer|exists:eir_inspections,id',
        ]);

        // Obtener o crear el registro de evidencia de techo
        $roof = RoofEvidence::firstOrCreate(
            ['eir_inspection_id' => $request->eir_id],
            [
                'qty_welded_patch' => 0,
                'qty_riveted_patch' => 0,
                'qty_rail_patch' => 0,
                'qty_welded_cut' => 0,
                'qty_sealed_cut' => 0,
                'has_additional_damages' => 0,
            ]
        );

        try {
            $roofEvidencePhotoService->validateImage($request->file('photo'));
            $result = $roofEvidencePhotoService->savePhoto($roof, $request->file('photo'));

            return response()->json([
                'success' => true,
                'photo' => [
                    'slot' => $result['slot'],
                    'url' => $result['url'],
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Subir foto de detalle
     */
    public function uploadDetailPhoto(Request $request, EirPhotoService $photoService)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:10240',
            'detail_id' => 'required|exists:eir_inspection_details,id',
        ]);

        try {
            $detail = EirInspectionDetail::findOrFail($request->detail_id);

            $photoService->validateImage($request->file('photo'));

            // Guarda y obtiene el modelo
            $newPhoto = $photoService->saveDetailPhoto($detail, $request->file('photo'));

            return response()->json([
                'success' => true,
                'photo' => [
                    'id' => $newPhoto->id,
                    'path' => $newPhoto->url, // Usa el accessor
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading detail photo', [
                'detail_id' => $request->detail_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir la foto'
            ], 500);
        }
    }

    public function generate_eir_pdf($eirId)
    {

        $eirData = EirInspection::with([
            'trace' => function ($query) {
                $query->where('status', '>', 0);
            },
            'trace.gateInDetail' => function ($query) {
                $query->whereHasMorph('originable', ['App\Models\Dischargue', 'App\Models\Devolution'], function ($morphQuery) {
                    $morphQuery->where('branch_id', session('branch')->id);
                });
            },
            'trace.gateInDetail.originable.branch',
            'trace.gateInDetail.originable.shippingLine',
            'trace.gateInDetail.originable.vessel',
            'trace.container',
            'inspector',
            'closer',
            'details.location',
            'details.damage',
            'details.component',
            'details.method',
            'details',
            'details.photos'      //las fotos
        ])
            ->whereHas('trace', function ($queryTrace) {
                $queryTrace->where('status', '>', 0)
                    ->whereHas('gateInDetail', function ($query) {
                        $query->whereHasMorph('originable', ['App\Models\Dischargue', 'App\Models\Devolution'], function ($morphQuery) {
                            $morphQuery->where('branch_id', session('branch')->id);
                        });
                    });
            })
            ->addSelect([
                'eir_inspections.*',
                'document_description' => Document::select('description')
                    ->where('id', 10)
                    ->limit(1)
            ])
            ->where('eir_inspections.id', $eirId)
            ->first();

        if (!$eirData) {
            return session()->flash('swal', [
                'icon' => 'error',
                'title' => 'Error!',
                'text' => 'No se ha encontrado el EIR en tu sucursal'
            ]);
        }
        $company = session('company');
        //dd(($eirData->container_category !== '' && $eirData->container_category) ? $eirData->container_category : '-' );
        $data = [
            'title' => 'Equipment Interchange Receipt',
            'headerData' => [
                'logo' => $company->logo_path ? Storage::url($company->logo_path) : asset('img/no-image.jpg'),
                'companyName' => $company->razonSocial,
                'documentTitle' => $eirData->document_description,
                'fechaGeneracion' => Carbon::parse(session('branch')->created_at)->format('d/m/Y'),
                'version' => $eirData->serie . '-' . session('branch')->id,
                'year' => Carbon::parse(session('branch')->created_at)->format('Y'),
            ],
            'numEir' => str_pad($eirData->correlativo, 6, 0, STR_PAD_LEFT),
            'fechaHoraInicio' => Carbon::parse($eirData->started_at)->format('d/m/Y H:i:s'),
            'inspector' => $eirData->closer->name ?? $eirData->inspector->name ,
            'codigoInspector' => $eirData->closer->numDoc ?? $eirData->inspector->numDoc,
            /* 'inspector' => $eirData->inspector->name,
            'codigoInspector' => $eirData->inspector->numDoc, */
            'contenedorId' => $eirData->trace->container->code,
            'iso' => $eirData->trace->container->iso_code,
            'tipo' => $eirData->trace->container->container_type->code,
            'tare' => $eirData->trace->container->tare,
            'fechaFabricacion' => str_pad($eirData->trace->container->build_month, 2, '0', STR_PAD_LEFT) . ' / ' . $eirData->trace->container->build_year,
            'containerClassLine' => ($eirData->container_category !== '' && $eirData->container_category) ? $eirData->container_category : '-',
            'payload' => $eirData->trace->container->payload,
            'maxGross' => $eirData->trace->container->max_gross,
            'maxHeight' => $eirData->trace->container->max_height,
            'nave' => strtoupper($eirData->trace->gateInDetail->originable->vessel->name),
            'viaje' => strtoupper($eirData->trace->gateInDetail->originable->voyage),
            'linea' => strtoupper($eirData->trace->gateInDetail->originable->shippingLine->name),
            'lavadoNormal' => $eirData->normal_wash,
            'lavadoQuimico' => $eirData->quimic_wash,
            'lavadoEspecial' => $eirData->special_wash,
            'lavadoOxalico' => $eirData->oxalic_wash,
            'motivoLavadoQuimico' => $eirData->quimic_wash ? $eirData->reason_quimic_wash : '',
            'motivoLavadoEspecial' => $eirData->special_wash ? $eirData->reason_special_wash : '',
            'damages' => $eirData->details->map(function ($detail) {
                $photos = $detail->photos;
                return [
                    'ubicacion' => $detail->location->code ?? '-',
                    'dano' => $detail->damage->code ?? '-',
                    'componente' => $detail->component->code ?? '-',
                    'metodo' => $detail->method->code ?? '-',
                    'approved' => $detail->approved,
                    'cantidad' => $detail->qty ?? '',
                    'largo' => $detail->length ?? '',
                    'ancho' => $detail->width ?? '',
                    'altura' => $detail->height ?? '',
                    'comentario' => $detail->comments ?? '',
                    'foto1' => $photos->get(0) ? $photos->get(0)->path : asset('img/no-image.jpg'),
                    'foto2' => $photos->get(1) ? $photos->get(1)->path : asset('img/no-image.jpg'),
                    'foto3' => $photos->get(2) ? $photos->get(2)->path : asset('img/no-image.jpg'),
                    'foto4' => $photos->get(3) ? $photos->get(3)->path : asset('img/no-image.jpg'),
                    /* 'foto1' => $photos->get(0) ? Storage::url($photos->get(0)->path) : asset('img/no-image.jpg'),
                    'foto2' => $photos->get(1) ? Storage::url($photos->get(1)->path) : asset('img/no-image.jpg'),
                    'foto3' => $photos->get(2) ? Storage::url($photos->get(2)->path) : asset('img/no-image.jpg'),
                    'foto4' => $photos->get(3) ? Storage::url($photos->get(3)->path) : asset('img/no-image.jpg'), */
                ];
            })->toArray(),
            'criterioIICL' => true,
            'criterioBASC' => true,
            'criterioCargoWorthy' => true,
            'footerData' => [
                'fechaHoraTermino' => $eirData->closed_at ? Carbon::parse($eirData->closed_at)->format('d/m/Y H:i:s') : '-',
                'fechaHoraReapertura' => $eirData->voided_at ? Carbon::parse($eirData->voided_at)->format('d/m/Y H:i:s') : '-',
            ],
        ];
        $pdfName = "EIR-{$eirId}.pdf";
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('dpi', 96);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        $html = view('pdf.eir', $data)->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream($pdfName, ['Attachment' => false]);
    }
}
