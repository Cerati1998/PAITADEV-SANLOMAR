<?php

namespace App\Console\Commands;

use App\Models\ContainerOperationalTrace;
use App\Models\Dispatches\Dispatch;
use App\Services\ExternalDispatchApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncDispatchedContainers extends Command
{
    protected $signature = 'dispatch:sync-containers 
                            {--week= : Número de semana específica (ISO 8601)} 
                            {--previous : Usar la semana anterior (cerrada)}
                            {--no-cache : No usar caché}';

    protected $description = 'Sincroniza contenedores despachados desde API externa';

    protected $apiService;

    public function __construct(ExternalDispatchApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        // Determinar qué semana procesar
        $week = $this->determineWeek();
        $year = Carbon::now()->year; // Siempre año actual
        $noCache = $this->option('no-cache');

        $this->info("═══════════════════════════════════════════════════");
        $this->info("  Sincronización de Contenedores Despachados");
        $this->info("═══════════════════════════════════════════════════");
        $this->info("Año: {$year} | Semana: {$week}");

        // Mostrar rango de fechas de la semana
        $weekStart = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $weekEnd = Carbon::now()->setISODate($year, $week)->endOfWeek();
        $this->info("Período: {$weekStart->format('d/m/Y')} - {$weekEnd->format('d/m/Y')}");
        $this->info("═══════════════════════════════════════════════════");
        $this->newLine();

        try {
            // 1. Obtener datos del API
            $apiContainers = $this->fetchApiContainers($week, $noCache);

            if (empty($apiContainers)) {
                $this->warn('⚠️  No se obtuvieron contenedores del API');
                return Command::SUCCESS;
            }

            $this->info("📥 Se obtuvieron " . count($apiContainers) . " contenedores del API");

            // 2. Obtener despachos locales de la semana (siempre año actual)
            $localDispatches = $this->getLocalDispatches($week);

            $this->info("💾 Se encontraron " . $localDispatches->count() . " despachos locales");
            $this->newLine();

            // 3. Comparar y actualizar
            $updated = $this->compareAndUpdate($apiContainers, $localDispatches, $week);

            $this->newLine();
            $this->info("═══════════════════════════════════════════════════");
            $this->info("✅ Proceso completado exitosamente");
            $this->info("📊 {$updated} contenedores actualizados a status DESPACHADO (5)");
            $this->info("═══════════════════════════════════════════════════");

            // Log del resumen
            Log::info('Sincronización de despachos completada', [
                'week' => $week,
                'year' => $year,
                'api_containers' => count($apiContainers),
                'local_dispatches' => $localDispatches->count(),
                'updated_containers' => $updated
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("═══════════════════════════════════════════════════");
            $this->error("❌ Error en la sincronización");
            $this->error("═══════════════════════════════════════════════════");
            $this->error($e->getMessage());

            Log::error('Error en SyncDispatchedContainers', [
                'week' => $week,
                'year' => $year,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Determinar qué semana procesar
     */
    private function determineWeek(): int
    {
        // Si se especifica una semana manualmente
        if ($this->option('week')) {
            return (int) $this->option('week');
        }

        // Si se especifica --previous, usar la semana anterior (cerrada)
        if ($this->option('previous')) {
            return Carbon::now()->subWeek()->weekOfYear;
        }

        // Por defecto, usar la semana actual
        return Carbon::now()->weekOfYear;
    }

    /**
     * Obtener contenedores del API externa con cache
     */
    private function fetchApiContainers(int $week, bool $noCache = false): array
    {
        $year = Carbon::now()->year; // Siempre año actual
        $cacheKey = "api_dispatched_containers_{$year}_week_{$week}";

        // Si se especifica --no-cache, limpiar el cache
        if ($noCache) {
            Cache::forget($cacheKey);
            $this->line('🔄 Cache limpiado, obteniendo datos frescos del API...');
        }

        // Cachear por 2 horas (7200 segundos)
        $cacheDuration = 7200;

        return Cache::remember($cacheKey, $cacheDuration, function () use ($week) {
            $this->line("🌐 Consultando API externa para semana {$week}...");

            $response = $this->apiService->getContainersByWeek($week);

            if (!$response['success']) {
                $errorMsg = $response['message'] ?? 'Error desconocido';
                Log::error('Error al consultar API externa', [
                    'week' => $week,
                    'error' => $errorMsg
                ]);
                throw new \Exception("Error al consultar API: {$errorMsg}");
            }

            $data = $response['data'];
            $this->info("✓ API respondió con " . count($data) . " registros");

            return $data;
        });
    }

    /**
     * Obtener despachos locales de la semana del año actual
     */
    private function getLocalDispatches(int $week)
    {
        // SIEMPRE usar el año actual
        $currentYear = Carbon::now()->year;

        $weekStart = Carbon::now()->setISODate($currentYear, $week)->startOfWeek();
        $weekEnd = Carbon::now()->setISODate($currentYear, $week)->endOfWeek();

        $this->line("🔍 Consultando despachos locales de {$currentYear}");
        $this->line("   Rango: {$weekStart->format('d/m/Y')} - {$weekEnd->format('d/m/Y')}");

        return Dispatch::whereBetween('dispatch_date', [$weekStart, $weekEnd])
            ->whereYear('dispatch_date', $currentYear) // Filtro adicional explícito por año
            ->with(['trace.container'])
            ->get();
    }

    /**
     * Comparar contenedores del API con despachos locales y actualizar
     */
    private function compareAndUpdate(array $apiContainers, $localDispatches, int $week): int
    {
        $updatedCount = 0;

        // Crear un set de contenedores locales para búsqueda rápida
        $localContainerCodes = $localDispatches
            ->pluck('trace.container.code')
            ->filter()
            ->unique()
            ->map(fn($code) => strtoupper(trim($code)))
            ->toArray();

        $this->info("📋 Contenedores registrados en despachos locales: " . count($localContainerCodes));

        // Mostrar algunos ejemplos de contenedores locales (primeros 5)
        if (count($localContainerCodes) > 0) {
            $examples = array_slice($localContainerCodes, 0, 5);
            $this->line("   Ejemplos: " . implode(', ', $examples) . (count($localContainerCodes) > 5 ? '...' : ''));
        }

        $this->newLine();
        $this->line("🔄 Procesando contenedores del API...");

        // Crear barra de progreso
        $bar = $this->output->createProgressBar(count($apiContainers));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Iniciando...');
        $bar->start();

        foreach ($apiContainers as $apiContainer) {
            $containerCode = strtoupper(trim($apiContainer['num_contenedor'] ?? ''));

            if (!$containerCode) {
                $bar->setMessage("Saltando contenedor sin código");
                $bar->advance();
                continue;
            }

            $bar->setMessage("Procesando: {$containerCode}");

            // Si el contenedor del API NO está en nuestros despachos locales
            if (!in_array($containerCode, $localContainerCodes)) {

                // Buscar el trace del contenedor con status entre 1 y 4
                $trace = ContainerOperationalTrace::whereHas('container', function ($query) use ($containerCode) {
                    $query->whereRaw('UPPER(code) = ?', [$containerCode]);
                })
                    ->whereBetween('status', [1, 4])
                    ->where('status', '!=', 5)
                    ->first();

                if ($trace) {
                    // Preparar información adicional del API
                    $currentYear = Carbon::now()->year;
                   /*  $apiInfo = sprintf(
                        "Sync API - Año %s Semana %s | Booking: %s | Nave: %s %s | Línea: %s | Shipper: %s | Fecha Despacho: %s",
                        $currentYear,
                        $week,
                        $apiContainer['reserva_booking'] ?? 'N/A',
                        $apiContainer['nave_booking'] ?? 'N/A',
                        $apiContainer['viaje_booking'] ?? '',
                        $apiContainer['linea_booking'] ?? 'N/A',
                        $apiContainer['nombre_shippers'] ?? 'N/A',
                        $apiContainer['fecha_despacho'] ?? 'N/A'
                    ); */

                    $previousStatus = $trace->status;

                    // Actualizar a status 5 (Despachado)
                    $trace->update([
                        'status' => 5,
                        'date_final_status' => Carbon::parse($apiContainer['fecha_despacho'] ?? now()),
                        //'observation' => $apiInfo
                    ]);

                    $updatedCount++;
                    $bar->setMessage("✓ Actualizado: {$containerCode}");

                    Log::info('Contenedor actualizado a status 5 por sincronización API', [
                        'container_code' => $containerCode,
                        'trace_id' => $trace->id,
                        'previous_status' => $previousStatus,
                        'year' => $currentYear,
                        'week' => $week,
                        'api_data' => $apiContainer
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->setMessage('Completado');
        $bar->finish();
        $this->newLine();

        return $updatedCount;
    }
}