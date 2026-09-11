<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectionExport implements FromCollection, WithEvents
{
    protected $datos;
    protected $titulo;

    public function __construct($datos, $titulo)
    {
        $this->datos = $datos;
        $this->titulo = $titulo;
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
                $templatePath = storage_path('app/templates/INSPECCIONES.xlsx');

                $reader = IOFactory::createReader('Xlsx');
                $reader->setLoadSheetsOnly('Hoja1');
                $spreadsheet = $reader->load($templatePath);

                // Obtener la hoja activa
                $sheet = $spreadsheet->getActiveSheet();

                // Columna INSPECTOR después de PROVEEDOR (J). Se inserta por código porque
                // la plantilla está gitignorada y no viaja con el deploy.
                $sheet->insertNewColumnBefore('K', 1);
                foreach ([2, 3] as $fila) {
                    $sheet->duplicateStyle($sheet->getStyle("J{$fila}"), "K{$fila}");
                }
                $sheet->getColumnDimension('K')->setWidth($sheet->getColumnDimension('J')->getWidth());
                $sheet->setCellValue('K2', 'INSPECTOR');

                // Escribir el título en A1
                $sheet->setCellValue('A1', $this->titulo);

                $filaPlantilla = 3; // Fila que contiene el formato base
                $numeroFila = 3;

                // Rellenar las filas con los datos
                foreach ($this->datos as $index => $dato) {
                    // Si no es la primera fila de datos, copiar el formato de la fila plantilla
                    if ($numeroFila > $filaPlantilla) {
                        foreach (range('A', 'M') as $col) {
                            // Copiar estilo de la fila plantilla
                            $sheet->duplicateStyle(
                                $sheet->getStyle("{$col}{$filaPlantilla}"),
                                "{$col}{$numeroFila}"
                            );
                        }
                        // Copiar altura de fila
                        $sheet->getRowDimension($numeroFila)->setRowHeight(
                            $sheet->getRowDimension($filaPlantilla)->getRowHeight()
                        );
                    }

                    $sheet->setCellValue("A{$numeroFila}", $index + 1);
                    $sheet->setCellValue("B{$numeroFila}", $dato['container']);
                    $sheet->setCellValue("C{$numeroFila}", $dato['size']);
                    $sheet->setCellValue("D{$numeroFila}", $dato['type']);
                    $sheet->setCellValue("E{$numeroFila}", $dato['year']);
                    $sheet->setCellValue("F{$numeroFila}", $dato['tare']);
                    $sheet->setCellValue("G{$numeroFila}", $dato['payload']);
                    $sheet->setCellValue("H{$numeroFila}", $dato['condition']);
                    $sheet->setCellValue("I{$numeroFila}", $dato['height']);
                    $sheet->setCellValue("J{$numeroFila}", $dato['provider']);
                    $sheet->setCellValue("K{$numeroFila}", $dato['inspector']);
                    $sheet->setCellValue("L{$numeroFila}", $dato['wash']);
                    $sheet->setCellValue("M{$numeroFila}", $dato['note']);
                    $numeroFila++;
                }

                // Reemplazar el delegate sheet con nuestro spreadsheet
                $event->writer->getDelegate()->removeSheetByIndex(0);
                $event->writer->getDelegate()->addExternalSheet($sheet);
            },
        ];
    }
}