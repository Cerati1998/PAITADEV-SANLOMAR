<?php

namespace App\Enums;

enum VoidedStatus: string
{
    case Aceptada = 'aceptada';
    case Pendiente = 'pendiente';
    case Rechazada = 'rechazada';

    public function label(): string
    {
        return match ($this) {
            self::Aceptada => 'ACEPTADA',
            self::Pendiente => 'PENDIENTE',
            self::Rechazada => 'RECHAZADA',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Aceptada => 'primary',
            self::Pendiente => 'warning',
            self::Rechazada => 'danger',
        };
    }
}
