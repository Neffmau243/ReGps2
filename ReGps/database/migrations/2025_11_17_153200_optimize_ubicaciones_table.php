<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            // Agregar índices compuestos para optimizar queries
            $table->index(['DispositivoID', 'FechaHora'], 'idx_dispositivo_fecha');
            $table->index(['FechaHora'], 'idx_fecha');
            $table->index(['Velocidad'], 'idx_velocidad');
            
            // Campo para marcar ubicaciones antiguas a eliminar
            $table->boolean('Archivado')->default(false)->after('FechaHora');
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropIndex('idx_dispositivo_fecha');
            $table->dropIndex('idx_fecha');
            $table->dropIndex('idx_velocidad');
            $table->dropColumn('Archivado');
        });
    }
};
