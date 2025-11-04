<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DispatchExport implements WithEvents
{
    protected $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templatePath = storage_path('app/templates/DESPACHOS.xlsx');

                $reader = IOFactory::createReader('Xlsx');
                $reader->setLoadSheetsOnly('Movimientos');

                $spreadsheet = $reader->load($templatePath);

                $spreadsheet->getProperties()
                    ->setCreator('Macrosoft 1.2')
                    ->setLastModifiedBy('Eddy Barranzuela')
                    ->setTitle("Reporte Macrosoft 1.2")
                    ->setSubject("Archivo para reporte de Movimientos GRUPOMACROMAR")
                    ->setDescription("Aréa de Sistemas de un solo miembro XD")
                    ->setCategory("Operaciones Macromar");

                $sheet = $spreadsheet->getActiveSheet();
                $numeroFila = 5;

                foreach ($this->datos as $dato) {
                    $sheet->setCellValue([1, $numeroFila], $dato['id_movimiento']);
                    $sheet->setCellValue([2, $numeroFila], $dato['fecha']);
                    $sheet->setCellValue([3, $numeroFila], $dato['categoria']);
                    $sheet->setCellValue([4, $numeroFila], $dato['codigo_producto']);
                    $sheet->setCellValue([5, $numeroFila], $dato['nombre_producto']);
                    $sheet->setCellValue([6, $numeroFila], $dato['unidades']);
                    $sheet->setCellValue([7, $numeroFila], $dato['nombre_proveedor']);
                    $sheet->setCellValue([8, $numeroFila], $dato['receptor_despacho']);
                    $sheet->setCellValue([9, $numeroFila], $dato['factura_movimiento']);
                    $sheet->setCellValue([10, $numeroFila], $dato['orden_servicio']);
                    $sheet->setCellValue([11, $numeroFila], $dato['destino_salida']);

                    $numeroFila++;
                }

                $event->writer->reopen($spreadsheet, $event->writer->getDefaultExtension());
            },
        ];
    }
}
