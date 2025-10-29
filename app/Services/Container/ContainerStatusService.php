<?php

namespace App\Services\Container;

class ContainerStatusService
{
    // Constantes para estados de entrada (Box y Machine)
    public const STATUS_OPERATIVO = 'OP';           // Operativo
    public const STATUS_OPERATIVO_SUCIO = 'OS';     // Operativo Sucio (SOLO BOX)
    public const STATUS_DANADO = 'DM';              // Dañado
    public const STATUS_PENDING = 'pending';        // Pendiente

    // Constantes para estados de salida (status del trace/contenedor)
    public const TRACE_ANULADO = 0;                 // Anulado
    public const TRACE_ANUNCIADO = 1;               // Anunciado
    public const TRACE_RECIBIDO = 2;                // Recibido
    public const TRACE_DANADO = 3;                  // Dañado (DM)
    public const TRACE_OPERATIVO = 4;               // Operativo (AV)
    public const TRACE_DESPACHADO = 5;              // Despachado
    public const TRACE_DEVOLUCION_INTERNA = 6;      // Devolución Interna

    /**
     * Calcula el estado global del contenedor
     * 
     * Estados válidos:
     * - Box: PENDING, dm, os, op
     * - Machine: PENDING, NULL, dm, op (NO tiene os)
     */
    public static function calculateStatus(
        ?string $statusBox = null,
        ?string $statusMachine = null,
        int $defaultStatus = self::TRACE_ANUNCIADO
    ): int {
        // Si no hay statusBox, retornar estado por defecto
        if (is_null($statusBox)) {
            return $defaultStatus;
        }

        // Normalizar a minúsculas
        $statusBox = strtoupper(trim($statusBox));
        $statusMachine = $statusMachine ? strtoupper(trim($statusMachine)) : null;

        // CASO 1: Si la máquina es NULL, el estado final depende solo de la caja
        if (is_null($statusMachine)) {
            return match ($statusBox) {
                'OP' => self::TRACE_OPERATIVO,      // OP → Operativo (4)
                'OS' => self::TRACE_OPERATIVO,      // OS → Operativo (4)
                'DM' => self::TRACE_DANADO,         // DM → Dañado (3)
                'PENDING' => $defaultStatus,        // PENDING → Estado por defecto
                default => $defaultStatus,
            };
        }

        // CASO 2: Si la máquina NO es NULL, evaluamos ambos estados

        // Si cualquiera está DAÑADO → Contenedor DAÑADO
        if ($statusBox === 'DM' || $statusMachine === 'DM') {
            return self::TRACE_DANADO;
        }

        // Si alguno está pendiente → Estado por defecto
        if ($statusBox === 'PENDING' && $statusMachine === 'PENDING') {
            return $defaultStatus;
        }

        // COMBINACIONES VÁLIDAS DE OPERATIVO
        // Box puede ser: op, os
        // Machine puede ser: op

        // Si ambos están OP → OPERATIVO
        if ($statusBox === 'OP' && $statusMachine === 'OP') {
            return self::TRACE_OPERATIVO;
        }

        // Si máquina OP y caja OS → OPERATIVO
        if ($statusMachine === 'OP' && $statusBox === 'OS') {
            return self::TRACE_OPERATIVO;
        }

        // Cualquier otro caso → Contenedor DAÑADO
        return self::TRACE_DANADO;
    }

    /**
     * Valida si un estado es válido
     * 
     * @param string $status Estado a validar
     * @param bool $isMachine Si es máquina (NO acepta OS)
     * @return bool
     */
    public static function isValidStatus(string $status, bool $isMachine = false): bool
    {
        $status = strtoupper(trim($status));

        if ($isMachine) {
            // Machine: PENDING, dm, op (NO OS)
            return in_array($status, ['PENDING', 'DM', 'OP']);
        } else {
            // Box: PENDING, dm, os, op
            return in_array($status, ['PENDING', 'DM', 'OS', 'OP']);
        }
    }

    /**
     * Obtiene la descripción del estado
     */
    public static function getStatusDescription(int $status): string
    {
        return match ($status) {
            self::TRACE_ANULADO => 'Anulado',
            self::TRACE_ANUNCIADO => 'Anunciado',
            self::TRACE_RECIBIDO => 'Recibido',
            self::TRACE_DANADO => 'Damage',
            self::TRACE_OPERATIVO => 'Operativo',
            self::TRACE_DESPACHADO => 'Despachado',
            self::TRACE_DEVOLUCION_INTERNA => 'Devolución Interna',
            default => 'Desconocido',
        };
    }
}
