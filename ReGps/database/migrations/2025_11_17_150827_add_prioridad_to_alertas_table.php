<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->enum('Prioridad', ['Baja', 'Media', 'Alta', 'Crítica'])->default('Media')->after('TipoAlerta');
            $table->unsignedBigInteger('AsignadoA')->nullable()->after('Estado');
            $table->text('Notas')->nullable()->after('AsignadoA');
            
            $table->foreign('AsignadoA')->references('UsuarioID')->on('usuarios')->onDelete('set null');
            $table->index(['Estado', 'Prioridad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alertas', function (Blueprint $table) {
            $table->dropForeign(['AsignadoA']);
            $table->dropColumn(['Prioridad', 'AsignadoA', 'Notas']);
        });
    }
};
