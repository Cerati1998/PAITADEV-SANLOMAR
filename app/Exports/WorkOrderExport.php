<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;

class WorkOrderExport implements FromCollection, WithEvents
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
                $templatePath = storage_path('app/templates/REPORTE_REP.xlsx');

                $reader = IOFactory::createReader('Xlsx');
                $reader->setLoadSheetsOnly('Hoja1');
                $spreadsheet = $reader->load($templatePath);

                // Obtener la hoja activa
                $sheet = $spreadsheet->getActiveSheet();
                $numeroFila = 4;

                //Relleno el titulo
                $sheet->setCellValue("A1", $this->title);
                // Rellenar las filas con los datos
                foreach ($this->datos as $index => $dato) {
                    $sheet->setCellValue("A{$numeroFila}", $index + 1);
                    $sheet->setCellValue("B{$numeroFila}", 1);
                    $sheet->setCellValue("C{$numeroFila}", $dato['year']);
                    $sheet->setCellValue("D{$numeroFila}", $dato['month']);
                    $sheet->setCellValue("E{$numeroFila}", $dato['week']);
                    $sheet->setCellValue("F{$numeroFila}", $dato['container']);
                    $sheet->setCellValue("G{$numeroFila}", $dato['type']);
                    $sheet->setCellValue("H{$numeroFila}", $dato['date_repair']);

                    // Aplicar borde a toda la fila
                    $sheet->getStyle("A{$numeroFila}:H{$numeroFila}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);

                    $numeroFila++;
                }

                // Reemplazar el delegate sheet con nuestro spreadsheet
                $event->writer->getDelegate()->removeSheetByIndex(0);
                $event->writer->getDelegate()->addExternalSheet($sheet);
            },
        ];
    }
}
