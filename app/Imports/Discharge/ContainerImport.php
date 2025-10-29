<?php

namespace App\Imports\Discharge;

use App\Models\Container;
use App\Models\ContainerOperationalTrace;
use App\Models\ContainerType;
use App\Models\Dischargue;
use App\Models\GateInDetail;
use App\Models\Port;
use App\Models\ReeferMachine;
use App\Models\ReeferTechnology;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Throwable;

class ContainerImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithBatchInserts
{
    protected $dischargueId;
    protected $lineId;

    // Cache en memoria durante el chunk
    protected array $cachedPorts = [];
    protected array $cachedContainerTypes = [];
    protected array $cachedReefers = [];

    public function __construct($dischargueId, $lineId)
    {
        $this->dischargueId = $dischargueId;
        $this->lineId = $lineId;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $data) {
            $row = $data->toArray();

            if (collect($row)->filter()->isEmpty()) {
                Log::info("Fila completamente vacía en línea " . ($index + 1));
                break;
            }

            try {
                //proceso de validacion de datos de cada contenedor y campo de proveniencia
                $containerCode = strtoupper(trim($row['container'] ?? ''));

                if (strlen($containerCode) !== 11) {
                    Log::error("Fila $index: Contenedor inválido → '{$containerCode}'");
                    throw new \Exception("El contenedor '{$row['container']}' no tiene 11 caracteres (tiene " . strlen($row['container']) . ")");
                }

                // Port
                $portCode = strtoupper(trim($row['pol'] ?? ''));
                if (!$portCode) {
                    Log::error("Fila $index: Código de puerto vacío.");
                    continue;
                }

                $port = $this->getCachedPort($portCode);

                // Container type
                $iso = strtoupper(trim($row['iso'] ?? ''));
                if (!$iso) {
                    Log::error("Fila $index: Código ISO vacío.");
                    continue;
                }


                $containerType = $this->getCachedContainerType($iso);

                // Reefer machine y tecnología (solo si es reefer)
                $reeferTechnologyId = null;

                if ($containerType->is_reefer) {
                    $type = strtoupper(trim($row['type'] ?? ''));

                    if (empty($type)) {
                        $type = 'CONVENCIONAL';
                        //Log::info("Fila $index: Contenedor reefer sin tipo, asignado tipo por defecto: CONVENCIONAL.");
                    }

                    $reeferTechnology = $this->getCachedTechnology($type);
                    $reeferTechnologyId = $reeferTechnology->id ?? NULL;
                }


                // Crear el contenedor, si lo encuentra solo trae
                $container = Container::firstOrCreate(
                    ['code' => $containerCode],
                    [
                        'iso_code' => $iso,
                        'container_type_id' => $containerType->id,
                        'reefer_machine_id' => null,
                        'reefer_technology_id' => $reeferTechnologyId,
                        'own_line_id' => $this->lineId,
                        //'origin_id' => $this->dischargueId,
                        //'origin_type' => \App\Models\Dischargue::class,
                    ]
                );

                //agrego validador, si el contenedor ya se encuentra anunciado aborto la subida
                $containerIn = ContainerOperationalTrace::where('container_id', $container->id)
                    ->where('status', '<', 5)
                    ->whereHas('gateInDetail.originable', function ($query) {
                        $query->where('branch_id', session('branch')->id);
                        //->where('originable_type', Dischargue::class);
                    })
                    ->exists();

                if ($containerIn) {
                    throw new \Exception("El contenedor '{$row['container']}' se encuentra en el patio, no se puede anunciar nuevamente.");
                }

                //creo el detalle del Gate In
                $gateInDetail = GateInDetail::create([
                    'container_id' => $container->id,
                    'port_id' => $port->id,
                    'originable_id' => $this->dischargueId,
                    'originable_type' => Dischargue::class,
                    'container_condition' => strtoupper(trim($row['condition'])) ?? 'MTY',
                ]);

                //creo el registro para la trazabilidad operativa del Contenedor
                ContainerOperationalTrace::create([
                    'gate_in_detail_id' => $gateInDetail->id,
                    'container_id' => $container->id,
                    'status_machine' => $containerType->is_reefer ? 'pending' : null
                ]);
            } catch (Throwable $e) {
                Log::error("Fila $index falló: " . $e->getMessage());
                throw new \Exception("Error en la fila $index: " . $e->getMessage(), 0, $e);
                // No lanzar excepción para evitar rollback completo
                //continue;
            }
        }
    }
    protected function getCachedTechnology(string $type)
    {

        if ($type == '') {
            return null;
        }
        $normalizedType = strtoupper(trim($type));

        if (isset($this->cachedReefers[$normalizedType])) {
            return $this->cachedReefers[$normalizedType];
        }

        // Buscar por coincidencia exacta (insensible a mayúsculas)
        $existing = ReeferTechnology::whereRaw('UPPER(TRIM(name)) = ?', [$normalizedType])->first();

        if (!$existing) {
            $existing = ReeferTechnology::create([
                'name' => $type,
            ]);
        }

        return $this->cachedReefers[$normalizedType] = $existing;
    }



    protected function getCachedPort(string $code): Port
    {
        if (isset($this->cachedPorts[$code])) {
            return $this->cachedPorts[$code];
        }

        return $this->cachedPorts[$code] = Port::firstOrCreate(['code' => $code], [
            'name' => $code,
        ]);
    }

    protected function getCachedContainerType(string $iso): ContainerType
    {
        if (isset($this->cachedContainerTypes[$iso])) {
            return $this->cachedContainerTypes[$iso];
        }

        return $this->cachedContainerTypes[$iso] = ContainerType::firstOrCreate(['iso_code' => $iso], [
            'description' => $iso,
        ]);
    }
}
