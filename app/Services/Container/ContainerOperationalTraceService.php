<?php

namespace App\Services\Container;

use App\Models\ContainerOperationalTrace;
use App\Services\Container\ContainerStatusService;
use Illuminate\Support\Facades\DB;
use Exception;

class ContainerOperationalTraceService
{
    /**
     * Actualiza el estado operacional del contenedor
     */
    public function updateStatus(
        int $traceId,
        ?string $statusBox = null,
        ?string $statusMachine = null,
        ?int $finalStatus = null,
        ?int $userId = null
    ): ContainerOperationalTrace {
        try {
            $trace = ContainerOperationalTrace::findOrFail($traceId);

            $updateData = [];

            // OBTENER LOS VALORES ACTUALES DEL TRACE
            // Si no se pasa statusBox, usar el actual de la BD
            $currentStatusBox = $statusBox ?? $trace->status_box;
            // Si no se pasa statusMachine, usar el actual de la BD
            $currentStatusMachine = $statusMachine ?? $trace->status_machine;

            // Actualizar status_box y su fecha si se proporciona uno NUEVO
            if (!is_null($statusBox)) {
                // Validar estado de box
                if (!ContainerStatusService::isValidStatus($statusBox, false)) {
                    throw new Exception("Estado de box inválido: {$statusBox}");
                }
                $updateData['status_box'] = $statusBox;
                $updateData['date_rep_box'] = now();
            }

            // Actualizar status_machine y su fecha si se proporciona uno NUEVO
            if (!is_null($statusMachine)) {
                // Validar estado de machine
                //dd($statusMachine);
                if (!ContainerStatusService::isValidStatus($statusMachine, true)) {
                    throw new Exception("Estado de machine inválido: {$statusMachine}");
                }
                $updateData['status_machine'] = $statusMachine;
                $updateData['date_rep_machine'] = now();
            }

            //  CALCULAR EL ESTADO FINAL usando los valores actuales + nuevos
            if (!is_null($finalStatus)) {
                $updateData['status'] = $finalStatus;
            } else {
                // Usar los valores actuales (que ya incluyen los nuevos si se pasaron)
                $calculatedStatus = ContainerStatusService::calculateStatus(
                    $currentStatusBox,
                    $currentStatusMachine
                );
                $updateData['status'] = $calculatedStatus;
            }

            // Siempre actualizar date_final_status cuando cambia el status
            if (isset($updateData['status'])) {
                $updateData['date_final_status'] = now();
            }

            // Actualizar usuario
            $updateData['final_status_user'] = $userId ?? auth()->id();

            $trace->update($updateData);

            return $trace->fresh();
        } catch (Exception $e) {
            throw new Exception("Error al actualizar el trace del contenedor: {$e->getMessage()}");
        }
    }

    /**
     * Actualiza el estado con cálculo automático
     *  SIEMPRE consulta los valores actuales antes de calcular
     */
    public function updateWithCalculatedStatus(
        int $traceId,
        ?string $statusBox = null,
        ?string $statusMachine = null
    ): ContainerOperationalTrace {
        return $this->updateStatus(
            traceId: $traceId,
            statusBox: $statusBox,
            statusMachine: $statusMachine,
            finalStatus: null // Forzar cálculo automático
        );
    }

    /**
     * Cierra el trace del contenedor completamente
     */
    public function closeTrace(
        int $traceId,
        string $statusBox,
        ?string $statusMachine = null
    ): ContainerOperationalTrace {
        return DB::transaction(function () use ($traceId, $statusBox, $statusMachine) {
            return $this->updateWithCalculatedStatus(
                $traceId,
                $statusBox,
                $statusMachine
            );
        });
    }
}
