<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


class PurgeLivewireTemp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purge:livewire-tmp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para limpiar archivos temporales de Livewire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tmpPath = storage_path('app/public/livewire-tmp');

        if (!File::exists($tmpPath)) {
            $this->info('No existe el directorio livewire-tmp.');
            return;
        }

        $files = File::allFiles($tmpPath);
        $count = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($file->getMTime(), 'America/Lima');
            // Solo borra archivos con más de 10 minutos de antigüedad
            $diff = now()->diffInMinutes($lastModified);
            if (abs($diff) > 10) {
                File::delete($file->getPathname());
                $count++;
            }
        }

        $this->info("Se eliminaron {$count} archivos temporales.");
    }
}
