<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ubicacion;

class EliminarUbicacionesArchivadas extends Command
{
    protected $signature = 'ubicaciones:eliminar-archivadas';
    protected $description = 'Elimina permanentemente las ubicaciones marcadas como archivadas';

    public function handle()
    {
        $count = Ubicacion::where('Archivado', true)->count();
        
        if ($count === 0) {
            $this->info('No hay ubicaciones archivadas para eliminar.');
            return 0;
        }
        
        $this->warn("⚠️  ADVERTENCIA: Esta acción es IRREVERSIBLE");
        
        if ($this->confirm("¿Está seguro de eliminar permanentemente {$count} ubicaciones archivadas?")) {
            Ubicacion::where('Archivado', true)->delete();
            $this->info("✓ {$count} ubicaciones eliminadas permanentemente.");
        } else {
            $this->info('Operación cancelada.');
        }
        
        return 0;
    }
}
