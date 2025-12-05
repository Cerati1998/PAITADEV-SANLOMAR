<?php

namespace App\Livewire;

use App\Models\ArchingCash;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Bill;

class BillTable extends DataTableComponent
{
    protected $model = Bill::class;

    public $openModal = false;
    public $editMode = false;
    public $billId = null;

    #[Validate('required|date')]
    public $fecha_emision;

    #[Validate('required|in:factura,boleta,recibo,ticket,otros')]
    public $tipo_comprobante = 'boleta';

    #[Validate('nullable|string|max:20')]

    public $numero_comprobante;

    #[Validate('nullable|exists:products,id')]
    public $product_id;

    #[Validate('nullable|exists:providers,id')]
    public $provider_id;

    #[Validate('nullable|exists:categories,id')]
    public $category_id;

    #[Validate('required|numeric|min:0.01')]
    public $monto;

    #[Validate('required|in:PEN,USD')]
    public $moneda = 'PEN';

    #[Validate('nullable|numeric|min:0.01')]
    public $tipo_cambio = 1;

    #[Validate('required|string|min:3')]
    public $detalle;

    #[Validate('nullable|string|max:200')]
    public $observaciones;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setConfigurableAreas([
            'after-wrapper' => ['bills.modal']
        ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->deselected(),
            Column::make('Acciones')
                ->label(function ($row) {
                    return view('bills.actions', ['bill' => $row]);
                }),
            Column::make("Correlativo", "correlativo")
                ->sortable(),
            Column::make("Fecha registro", "fecha_registro")
                ->sortable(),
            Column::make("Comprobante", "numero_comprobante")
                ->sortable(),
            Column::make("Proveedor", "provider.rznSocial")
                ->sortable(),
            Column::make("Monto", "monto")
                ->sortable(),
            Column::make("Moneda", "moneda")
                ->sortable(),
            Column::make("Creado", "user.name")
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        return Bill::with(['provider', 'branch', 'user'])
            ->where('bills.branch_id', session('branch')->id);
    }

    #[Computed]
    public function cajaActual()
    {
        if (!session('branch')->id) {
            return null;
        }

        return ArchingCash::where('branch_id', session('branch')->id)
            ->where('estado', 'abierta')
            ->whereNull('fecha_fin')
            ->first();
    }

      #[Computed]
    public function saldoDisponible()
    {
        if (!$this->cajaActual) {
            return 0;
        }

        // Solo sumar depósitos adicionales, NO el inicial
        $depositosAdicionales = $this->cajaActual->deposits()
            ->whereIn('tipo_deposito', ['adicional', 'reembolso'])
            ->sum('monto');

        return $this->cajaActual->monto_inicial + $depositosAdicionales - $this->cajaActual->total_salidas;
    }

    #[Computed]
    public function products()
    {
        return Product::where('branch_id', session('branch')->id)
            ->orderBy('descripcion')
            ->get();
    }

   /*  #[Computed]
    public function providers()
    {
        return Provider::where('branch_id', session('branch')->id)
            ->orderBy('rznSocial')
            ->get();
    } */

    public function edit($id)
    {
        $bill = Bill::find($id);

        if (!$bill) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Gasto no encontrado',
                'icon' => 'error'
            ]);
            return;
        }

        // No permitir editar si la caja está cerrada
        if ($bill->archingCash && $bill->archingCash->estado != 'abierta') {
            $this->dispatch('swal', [
                'title' => 'Caja cerrada',
                'text' => 'No se pueden editar gastos de una caja cerrada',
                'icon' => 'warning'
            ]);
            return;
        }

        $this->billId = $bill->id;
        $this->fecha_emision = $bill->fecha_emision->format('Y-m-d');
        $this->tipo_comprobante = $bill->tipo_comprobante;
        $this->numero_comprobante = $bill->numero_comprobante;
        $this->product_id = $bill->product_id;
        $this->provider_id = $bill->provider_id;
        $this->monto = $bill->monto;
        $this->moneda = $bill->moneda;
        $this->tipo_cambio = $bill->tipo_cambio ?? 1;
        $this->detalle = $bill->detalle;
        $this->observaciones = $bill->observaciones;

        $this->editMode = true;
        $this->openModal = true;
    }

    public function save()
    {
        // Validar que haya una caja abierta
        if (!$this->cajaActual) {
            $this->dispatch('swal', [
                'title' => 'Sin caja abierta',
                'text' => 'Debe aperturar una caja antes de registrar gastos',
                'icon' => 'warning'
            ]);
            return;
        }

        // Calcular monto en soles
        $montoEnSoles = $this->moneda === 'PEN' 
            ? $this->monto 
            : $this->monto * ($this->tipo_cambio ?? 1);

        // Validar saldo disponible
        if (!$this->editMode) {
            if ($montoEnSoles > $this->saldoDisponible) {
                $this->dispatch('swal', [
                    'title' => 'Saldo insuficiente',
                    'text' => 'El monto del gasto (S/ ' . number_format($montoEnSoles, 2) . ') supera el saldo disponible (S/ ' . number_format($this->saldoDisponible, 2) . ')',
                    'icon' => 'error'
                ]);
                return;
            }
        } else {
            // En edición, considerar el monto anterior
            $billAnterior = Bill::find($this->billId);
            $montoAnteriorEnSoles = $billAnterior->moneda === 'PEN' 
                ? $billAnterior->monto 
                : $billAnterior->monto * ($billAnterior->tipo_cambio ?? 1);
            
            $diferencia = $montoEnSoles - $montoAnteriorEnSoles;
            
            if ($diferencia > $this->saldoDisponible) {
                $this->dispatch('swal', [
                    'title' => 'Saldo insuficiente',
                    'text' => 'El incremento del gasto supera el saldo disponible',
                    'icon' => 'error'
                ]);
                return;
            }
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $billData = [
                'fecha_emision' => $this->fecha_emision,
                'tipo_comprobante' => $this->tipo_comprobante,
                'numero_comprobante' => $this->numero_comprobante,
                'product_id' => $this->product_id,
                'provider_id' => $this->provider_id,
                'monto' => $this->monto,
                'moneda' => $this->moneda,
                'tipo_cambio' => $this->moneda === 'USD' ? $this->tipo_cambio : 1,
                'detalle' => $this->detalle,
                'observaciones' => $this->observaciones,
                'branch_id' => session('branch')->id,
                'user_id' => auth()->id(),
            ];

            if ($this->editMode) {
                // EDITAR
                $bill = Bill::find($this->billId);
                $montoAnteriorEnSoles = $bill->moneda === 'PEN' 
                    ? $bill->monto 
                    : $bill->monto * ($bill->tipo_cambio ?? 1);

                // Actualizar el bill
                $bill->update($billData);

                // Actualizar total_salidas de la caja
                $diferencia = $montoEnSoles - $montoAnteriorEnSoles;
                if ($diferencia != 0) {
                    $this->cajaActual->increment('total_salidas', $diferencia);
                }

                $mensaje = 'Gasto actualizado correctamente';

            } else {
                // CREAR
                // Generar correlativo
                $ultimoCorrelativo = Bill::where('branch_id', session('branch')->id)
                    ->orderBy('id', 'desc')
                    ->value('correlativo');

                $numero = 1;
                if ($ultimoCorrelativo) {
                    preg_match('/\d+$/', $ultimoCorrelativo, $matches);
                    $numero = isset($matches[0]) ? intval($matches[0]) + 1 : 1;
                }

                $billData['correlativo'] = 'GS-' . str_pad($numero, 5, '0', STR_PAD_LEFT);
                $billData['arching_cash_id'] = $this->cajaActual->id;
                $billData['estado'] = 'pagado';
                $billData['fecha_registro'] = now();

                $bill = Bill::create($billData);

                // Actualizar total_salidas de la caja
                $this->cajaActual->increment('total_salidas', $montoEnSoles);

                $mensaje = "Gasto registrado correctamente con correlativo: {$bill->correlativo}";
            }

            DB::commit();

            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => $mensaje,
                'icon' => 'success'
            ]);

            $this->dispatch('billAdded');
            $this->closeModal();

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Ocurrió un error: ' . $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function delete($id)
    {
        $bill = Bill::find($id);

        if (!$bill) {
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Gasto no encontrado',
                'icon' => 'error'
            ]);
            return;
        }

        // No permitir eliminar si la caja está cerrada
        if ($bill->archingCash && $bill->archingCash->estado != 1) {
            $this->dispatch('swal', [
                'title' => 'Caja cerrada',
                'text' => 'No se pueden eliminar gastos de una caja cerrada',
                'icon' => 'warning'
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            // Calcular monto en soles
            $montoEnSoles = $bill->moneda === 'PEN' 
                ? $bill->monto 
                : $bill->monto * ($bill->tipo_cambio ?? 1);

            // Actualizar total_salidas de la caja
            if ($bill->archingCash) {
                $bill->archingCash->decrement('total_salidas', $montoEnSoles);
            }

            // Cambiar estado a anulado en lugar de eliminar
            $bill->delete();

            DB::commit();

            $this->dispatch('swal', [
                'title' => '¡Éxito!',
                'text' => 'Gasto anulado correctamente',
                'icon' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Ocurrió un error: ' . $e->getMessage(),
                'icon' => 'error'
            ]);
        }
    }

    public function closeModal()
    {
          $this->reset([
            'openModal','editMode', 'billId', 'fecha_emision', 'tipo_comprobante',
            'numero_comprobante', 'product_id', 'provider_id', 'monto',
             'tipo_cambio', 'detalle', 'observaciones'
        ]);
    }

}
