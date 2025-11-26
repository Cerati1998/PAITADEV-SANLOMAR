<?php

namespace App\Livewire;

use App\Models\ArchingCash;
use App\Models\CashDeposit;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

class CashDepositCreate extends Component
{
    public $arching_cash_id;
    public $archingCash;
    public $openModal;

    #[Validate('required|in:adicional,reembolso')]
    public $tipo_deposito = 'adicional';

    #[Validate('required|numeric|min:0.01')]
    public $monto;

    #[Validate('required|in:PEN,USD')]
    public $moneda = 'PEN';

    #[Validate('required|date')]
    public $fecha_deposito;

    #[Validate('required|in:efectivo,transferencia,cheque')]
    public $metodo = 'efectivo';

    #[Validate('nullable|string|max:100')]
    public $numero_operacion;

    #[Validate('nullable|string|max:500')]
    public $detalle;

    #[Validate('nullable|file|mimes:jpg,jpeg,png,pdf|max:2048')]
    public $comprobante;

    public $branch_id;

    public function mount($archingCashId = null)
    {
        if ($archingCashId) {
            $this->arching_cash_id = $archingCashId;
            $this->archingCash = ArchingCash::find($archingCashId);
        }

        $this->branch_id = session('branch')->id ?? null;
        $this->fecha_deposito = now()->format('Y-m-d\TH:i');
    }

    #[On('open-deposit-modal')]
    public function setCashId($cashId)
    {
        $this->arching_cash_id = $cashId;
        $this->archingCash = ArchingCash::find($cashId);
        $this->branch_id = $this->archingCash->branch_id ?? session('branch')->id;

        // Resetear campos del formulario
        $this->tipo_deposito = 'adicional';
        $this->monto = null;
        $this->moneda = 'PEN';
        $this->metodo = 'transferencia';
        $this->numero_operacion = null;
        $this->detalle = null;
        $this->comprobante = null;
        $this->fecha_deposito = now()->format('Y-m-d\TH:i');
        $this->openModal = true;
    }

    public function updatedMetodo($value)
    {
        // Si el método es efectivo, limpiar número de operación
        if ($value === 'efectivo') {
            $this->numero_operacion = null;
        }
    }

    public function save()
    {
        // Validar que la caja esté abierta
        if (!$this->archingCash || $this->archingCash->estado !== 'abierta') {
            $this->dispatch(
                'swal',
                [
                    'title' => 'Error',
                    'text' => 'La caja debe estar abierta para registrar depósitos',
                    'icon' => 'warning'
                ]
            );
            return;
        }

        $this->validate();

        // Validación adicional para número de operación
        if (in_array($this->metodo, ['transferencia', 'cheque']) && empty($this->numero_operacion)) {
            $this->addError('numero_operacion', 'El número de operación es requerido para este método de pago');
            return;
        }

        try {
            DB::beginTransaction();

            $comprobanteUrl = null;

            // Subir archivo de comprobante si existe
            if ($this->comprobante) {
                $filename = 'deposit_' . time() . '_' . uniqid() . '.' . $this->comprobante->extension();
                $comprobanteUrl = $this->comprobante->storeAs('deposits', $filename, 'public');
            }

            // Crear el depósito
            $deposit = CashDeposit::create([
                'arching_cash_id' => $this->arching_cash_id,
                'tipo_deposito' => $this->tipo_deposito,
                'monto' => $this->monto,
                'moneda' => $this->moneda,
                'fecha_deposito' => $this->fecha_deposito,
                'metodo' => $this->metodo,
                'numero_operacion' => $this->numero_operacion,
                'detalle' => $this->detalle,
                'comprobante' => $comprobanteUrl,
                'user_id' => auth()->id(),
                'branch_id' => $this->branch_id,
            ]);

            // Actualizar el total de ingresos de la caja
            $this->archingCash->increment('total_ingresos', $this->monto);

            DB::commit();

            $this->dispatch(
                'swal',
                [
                    'title' => 'Éxito',
                    'text' => 'Depósito registrado correctamente',
                    'icon' => 'success'
                ]
            );

            $this->dispatch('depositAdded');
            $this->dispatch('cashUpdated');
            $this->dispatch('close-modal', 'depositCreate');
            $this->reset(['monto', 'tipo_deposito', 'metodo', 'numero_operacion', 'detalle', 'comprobante', 'openModal']);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch(
                'swal',
                [
                    'title' => 'Error',
                    'text' => 'Ocurrió un error al registrar el depósito: ' . $e->getMessage(),
                    'icon' => 'error'
                ]
            );
        }
    }

    public function render()
    {
        return view('livewire.cash-deposit-create');
    }
}