<?php

namespace App\Livewire;

use App\Models\ArchingCash;
use App\Models\Branch;
use App\Models\CashDeposit;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

class ArchingCashCreate extends Component
{

    #[Validate('required|date')]
    public $fecha_inicio;

    #[Validate('required|numeric|min:0')]
    public $deposito_inicial = 0;

    #[Validate('boolean')]
    public $heredar_saldo = false;

    public $observaciones;

    public $branch_id;
    public $existeCajaAbierta = false;

    #[Computed]
    public function ultimaCaja()
    {
        if (!$this->branch_id) {
            return null;
        }

        return ArchingCash::where('branch_id', $this->branch_id)
            ->whereNotNull('fecha_fin')
            ->orderBy('fecha_fin', 'desc')
            ->first();
    }

    #[Computed]
    public function saldoHeredado()
    {
        if (!$this->heredar_saldo || !$this->ultimaCaja) {
            return 0;
        }

        return $this->ultimaCaja->monto_final ?? 0;
    }

    #[Computed]
    public function montoInicial()
    {
        return $this->saldoHeredado + ($this->deposito_inicial ?? 0);
    }

    //#[Computed]
    public function validaCajaAbierta()
    {
        //Log::info($this->branch_id);
        if (!$this->branch_id) {
            return false;
        }
        return ArchingCash::where('branch_id', $this->branch_id)
            ->where('estado', 'abierta')
            ->exists();
    }

    #[On('cashUpdated')]
    public function refreshArching()
    {
        $this->existeCajaAbierta = $this->validaCajaAbierta();
    }


    public function updatedHeredarSaldo()
    {
        // Trigger recalculation
        $this->dispatch('saldoActualizado');
    }

    public function updatedDepositoInicial()
    {
        // Trigger recalculation
        $this->dispatch('montoActualizado');
    }

       public function mount()
    {
        $this->branch_id = session('branch')->id ?? null;
        $this->existeCajaAbierta =  $this->validaCajaAbierta();
        $this->fecha_inicio = now()->format('Y-m-d\TH:i');
        //dd($this->ultimaCaja());
    }

    public function save()
    {
        // Validar que no exista una caja abierta
        if ($this->existeCajaAbierta) {
            $this->dispatch(
                'swal',
                [
                    'title' => 'Error',
                    'text' => 'Ya existe una caja abierta para esta sucursal. Debe cerrarla antes de abrir una nueva.',
                    'icon' => 'error'
                ]
            );
            return;
        }

        $this->validate();

        // Validación adicional
        if ($this->montoInicial <= 0) {
            $this->dispatch(
                'swal',
                [
                    'title' => 'Error!',
                    'text' => 'El monto inicial debe ser mayor a 0',
                    'icon' => 'warning'
                ]
            );
        }

        try {
            DB::beginTransaction();

            // Generar correlativo
            $ultimoCorrelativo = ArchingCash::where('branch_id', $this->branch_id)
                ->orderBy('id', 'desc')
                ->value('correlativo');

            $numero = 1;
            if ($ultimoCorrelativo) {
                // Extraer el número del correlativo (ej: CC-001 -> 1)
                preg_match('/\d+$/', $ultimoCorrelativo, $matches);
                $numero = isset($matches[0]) ? intval($matches[0]) + 1 : 1;
            }

            $correlativo = 'CC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

            // Crear la caja
            $archingCash = ArchingCash::create([
                'correlativo' => $correlativo,
                'fecha_inicio' => $this->fecha_inicio,
                'saldo_heredado' => $this->saldoHeredado,
                'deposito_inicial' => $this->deposito_inicial,
                'monto_inicial' => $this->montoInicial,
                'total_salidas' => 0,
                'total_ingresos' => 0,
                'estado' => 1, // 1 = abierta
                'observaciones' => $this->observaciones,
                'opened_by' => auth()->id(),
                'branch_id' => $this->branch_id,
                'previous_arching_id' => $this->ultimaCaja?->id,
            ]);

            // Si se heredó saldo, registrar el depósito inicial
            if ($this->deposito_inicial > 0) {
                CashDeposit::create([
                    'arching_cash_id' => $archingCash->id,
                    'tipo_deposito' => 'inicial',
                    'monto' => $this->deposito_inicial,
                    'moneda' => 'PEN',
                    'fecha_deposito' => $this->fecha_inicio,
                    'metodo' => 'efectivo',
                    'detalle' => 'Depósito inicial de apertura',
                    'user_id' => auth()->id(),
                    'branch_id' => $this->branch_id,
                ]);
            }

            DB::commit();

            $this->dispatch(
                'swal',
                [
                    'title' => 'Éxito!',
                    'text' => "Caja aperturada correctamente con correlativo: {$correlativo}",
                    'icon' => 'success'
                ]
            );

            $this->dispatch('archingAdded');
            $this->dispatch('close-modal', 'archingCreate');
            $this->reset(['fecha_inicio','deposito_inicial', 'heredar_saldo', 'observaciones']);
            $this->refreshArching();

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch(
                'swal',
                [
                    'title' => 'Error!',
                    'text' => 'Ocurrió un error al aperturar la caja: ' . $e->getMessage(),
                    'icon' => 'error'
                ]
            );
        }
    }

    public function render()
    {
        return view('livewire.arching-cash-create');
    }
}