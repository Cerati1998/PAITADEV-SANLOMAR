<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvoiceExport implements FromCollection, WithHeadings, WithTitle
{
    protected $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        return new Collection($this->datos);
    }

    public function headings(): array
    {
        return [
            'Fecha Emisión',
            'Tipo Documento',
            'Serie-Correlativo',
            'Cliente',
            'Moneda',
            'Tipo Cambio',
            'Tipo Operación',
            'Detracción',
            'Subtotal',
            'IGV',
            'Total',
            'Estado SUNAT',
        ];
    }

    public function title(): string
    {
        return 'Comprobantes';
    }

    public static function estadoSunat(?array $sunatResponse, bool $voided): string
    {
        if ($voided) {
            return 'Anulado';
        }

        if (empty($sunatResponse)) {
            return 'Pendiente';
        }

        if (($sunatResponse['success'] ?? false) === true) {
            return 'Aceptado';
        }

        return 'Rechazado';
    }
}
