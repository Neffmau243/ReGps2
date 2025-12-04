<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ubicacion;
use Carbon\Carbon;

class LimpiarUbicacionesAntiguas extends Command
{
    protected $signature = 'ubicaciones:limpiar {--dias=90 : Días de antigüedad para eliminar}';
    protected $description = 'Elimina ubicaciones antiguas para optimizar la base de datos';

    public function handle()
    {
        $dias = $this->option('dias');
        $fecha = Carbon::now()->subDays($dias);
        
        $this->info("Buscando ubicaciones anteriores a: {$fecha->format('Y-m-d')}");
        
        $count = Ubicacion::where('FechaHora', '<', $fecha)
            ->where('Archivado', false)
            ->count();
        
        if ($count === 0) {
            $this->info('No hay ubicaciones antiguas para limpiar.');
            return 0;
        }
        
        if ($this->confirm("Se encontraron {$count} ubicaciones. ¿Desea marcarlas como archivadas?")) {
            Ubicacion::where('FechaHora', '<', $fecha)
                ->where('Archivado', false)
                ->update(['Archivado' => true]);
            
            $this->info("✓ {$count} ubicaciones marcadas como archivadas.");
            $this->warn("Nota: Para eliminarlas permanentemente, ejecute: php artisan ubicaciones:eliminar-archivadas");
        }
        
        return 0;
    }
}
