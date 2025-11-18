<?php

namespace App\Http\Controllers;

use App\Models\inspections\EirInspection;
use App\Models\inspections\LeakTest;
use App\Models\works\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [];
        $branch = session('branch')->id;

        // Datos actuales (hoy)
        $data['total_inspections'] = EirInspection::whereBetween('started_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ])->where('branch_id', $branch)->count();

        $data['total_works'] = WorkOrder::whereBetween('started_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ])->where('branch_id', $branch)->count();

        $data['total_leaks'] = LeakTest::whereBetween('started_at', [
            Carbon::today(),
            Carbon::today()->endOfDay()
        ])->where('branch_id', $branch)->count();

        $data['recent_repairs'] = WorkOrder::with(['container'])
            ->whereNotNull('closed_at')
            ->where('branch_id', $branch)
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('data'));
    }

    /**
     * API endpoint para obtener datos de gráficos
     */
    public function getChartData(Request $request)
    {
        try {
            $branch = session('branch')->id;

            $data = [
                'weekly_data' => $this->getWeeklyData($branch),
                'monthly_data' => $this->getMonthlyData($branch),
                'repair_types' => $this->getRepairTypesData($branch),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene datos de los últimos 7 días para el gráfico de tendencia semanal
     */
    private function getWeeklyData($branchId)
    {
        $days = [];
        $inspections = [];
        $works = [];
        $leaks = [];

        // Generar los últimos 7 días
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->locale('es')->isoFormat('ddd'); // Lun, Mar, etc.

            // Contar inspecciones del día
            $inspections[] = EirInspection::whereDate('started_at', $date)
                ->where('branch_id', $branchId)
                ->count();

            // Contar reparaciones del día
            $works[] = WorkOrder::whereDate('started_at', $date)
                ->where('branch_id', $branchId)
                ->count();

            // Contar leak tests del día
            $leaks[] = LeakTest::whereDate('started_at', $date)
                ->where('branch_id', $branchId)
                ->count();
        }

        return [
            'days' => $days,
            'inspections' => $inspections,
            'works' => $works,
            'leaks' => $leaks,
        ];
    }

    /**
     * Obtiene datos de los últimos 6 meses para el gráfico de comparativa mensual
     */
    private function getMonthlyData($branchId)
    {
        $months = [];
        $inspections = [];
        $works = [];
        $leaks = [];

        // Generar los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::today()->subMonths($i);
            $months[] = ucfirst($date->locale('es')->isoFormat('MMM')); // Ene, Feb, etc.

            // Contar inspecciones del mes
            $inspections[] = EirInspection::whereYear('started_at', $date->year)
                ->whereMonth('started_at', $date->month)
                ->where('branch_id', $branchId)
                ->count();

            // Contar reparaciones del mes
            $works[] = WorkOrder::whereYear('started_at', $date->year)
                ->whereMonth('started_at', $date->month)
                ->where('branch_id', $branchId)
                ->count();

            // Contar leak tests del mes
            $leaks[] = LeakTest::whereYear('started_at', $date->year)
                ->whereMonth('started_at', $date->month)
                ->where('branch_id', $branchId)
                ->count();
        }

        return [
            'months' => $months,
            'inspections' => $inspections,
            'works' => $works,
            'leaks' => $leaks,
        ];
    }

    /**
     * Obtiene la distribución de tipos de reparación del último mes
     */
    private function getRepairTypesData($branchId)
    {
        $lastMonth = Carbon::today()->subMonth();

        // Contar inspecciones por tipo de reparación del último mes
        $fastRepair = EirInspection::where('fast_repair', true)
            ->where('branch_id', $branchId)
            ->where('started_at', '>=', $lastMonth)
            ->count();

        $normalRepair = EirInspection::where('normal_repair', true)
            ->where('branch_id', $branchId)
            ->where('started_at', '>=', $lastMonth)
            ->count();

        $quotedRepair = EirInspection::where('quoted_repair', true)
            ->where('branch_id', $branchId)
            ->where('started_at', '>=', $lastMonth)
            ->count();

        return [
            'labels' => ['Reparación Rápida', 'Reparación Normal', 'Reparación Cotizada'],
            'values' => [$fastRepair, $normalRepair, $quotedRepair],
        ];
    }
}