<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Collection;

class DispatchExport implements FromCollection, WithEvents
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function collection()
    {
        // Retorna una colección vacía porque usaremos el template
        return new Collection([]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                // Cargar el template
                $templatePath = storage_path('app/templates/DESPACHOS.xlsx');
                
                $reader = IOFactory::createReader('Xlsx');
                $reader->setLoadSheetsOnly('Hoja1');
                $spreadsheet = $reader->load($templatePath);
                
                // Obtener la hoja activa
                $sheet = $spreadsheet->getActiveSheet();
                $numeroFila = 4;

                // Rellenar las filas con los datos
                foreach ($this->datos as $index => $dato) {
                    $sheet->setCellValue("A{$numeroFila}", $index + 1);
                    $sheet->setCellValue("B{$numeroFila}", $dato['container']);
                    $sheet->setCellValue("C{$numeroFila}", $dato['despacho']);
                    $sheet->setCellValue("D{$numeroFila}", $dato['booking']);
                    $sheet->setCellValue("E{$numeroFila}", $dato['nave']);
                    $sheet->setCellValue("F{$numeroFila}", $dato['despachador']);
                    $sheet->setCellValue("G{$numeroFila}", $dato['repair_box']);
                    $sheet->setCellValue("H{$numeroFila}", $dato['wash_oxalic']);
                    $sheet->setCellValue("I{$numeroFila}", $dato['leak_test']);
                    $numeroFila++;
                }

                // Reemplazar el delegate sheet con nuestro spreadsheet
                $event->writer->getDelegate()->removeSheetByIndex(0);
                $event->writer->getDelegate()->addExternalSheet($sheet);
            },
        ];
    }
}