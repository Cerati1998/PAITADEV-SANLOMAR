<?php

namespace App\Http\Controllers;

use App\Models\inspections\EirInspection;
use App\Models\inspections\LeakTest;
use App\Models\works\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [];
        $branch = session('branch')->id;
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
            ->latest()->take(5)->get();

        return view('dashboard', compact('data'));
    }
}
