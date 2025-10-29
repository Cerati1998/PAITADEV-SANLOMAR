<?php

namespace App\Observers;

use App\Models\Dischargue;
use Illuminate\Support\Facades\Auth;

class DischargueObserver
{

    public function creating(Dischargue $dischargue)
    {

        // Solo si está autenticado el usuario y hay una sucursal
        if (Auth::check() && session()->has('branch')) {
            $dischargue->created_by = Auth::id();
            $dischargue->branch_id = session('branch')->id;
        }
    }

    /**
     * Handle the Dischargue "created" event.
     */
    public function created(Dischargue $dischargue): void
    {
        //
    }

    /**
     * Handle the Dischargue "updated" event.
     */
    public function updated(Dischargue $dischargue): void
    {
        //
    }

    /**
     * Handle the Dischargue "deleted" event.
     */
    public function deleted(Dischargue $dischargue): void
    {
        $dischargue->anulated_by = Auth::id();
        $dischargue->saveQuietly();
    }

    /**
     * Handle the Dischargue "restored" event.
     */
    public function restored(Dischargue $dischargue): void
    {
        //
    }

    /**
     * Handle the Dischargue "force deleted" event.
     */
    public function forceDeleted(Dischargue $dischargue): void
    {
        //
    }
}
