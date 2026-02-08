<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Collection;

class DispatchExport implements FromCollection, WithEvents
{
    protected $title;
    protected $datos;


    public function __construct($datos)
    {
        $this->title = $datos['title'];
        $this->datos = $datos['datos'];
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
                $numeroFila = 5;

                //Relleno el titulo
                $sheet->setCellValue("A1", $this->title);
                // Rellenar las filas con los datos
                foreach ($this->datos as $index => $dato) {
                    $sheet->setCellValue("A{$numeroFila}", $index + 1);
                    $sheet->setCellValue("B{$numeroFila}", $dato['container']);
                    $sheet->setCellValue("C{$numeroFila}", $dato['fecha_dispatch']);
                    $sheet->setCellValue("D{$numeroFila}", $dato['week_dispatch']);
                    $sheet->setCellValue("E{$numeroFila}", $dato['booking_dispatch']);
                    $sheet->setCellValue("F{$numeroFila}", $dato['nave_dispatch']);
                    $sheet->setCellValue("G{$numeroFila}", $dato['name_dispatcher']);
                    $sheet->setCellValue("H{$numeroFila}", $dato['has_washed']);
                    $sheet->setCellValue("I{$numeroFila}", $dato['has_eir']);
                    $sheet->setCellValue("J{$numeroFila}", $dato['has_work_order']);
                    $sheet->setCellValue("K{$numeroFila}", $dato['has_leak_test']);
                    $sheet->setCellValue("L{$numeroFila}", $dato['has_oxalic_wash']);
                    $numeroFila++;
                }

                // Reemplazar el delegate sheet con nuestro spreadsheet
                $event->writer->getDelegate()->removeSheetByIndex(0);
                $event->writer->getDelegate()->addExternalSheet($sheet);
            },
        ];
    }
}