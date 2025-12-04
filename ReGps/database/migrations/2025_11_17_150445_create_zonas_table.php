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
        Schema::create('zonas', function (Blueprint $table) {
            $table->id('ZonaID');
            $table->string('Nombre', 100);
            $table->enum('TipoZona', ['Checkpoint', 'Zona Permitida', 'Zona Restringida'])->default('Checkpoint');
            $table->enum('TipoGeometria', ['Circulo', 'Poligono'])->default('Circulo');
            $table->decimal('Latitud', 10, 8); // Centro para círculos
            $table->decimal('Longitud', 11, 8); // Centro para círculos
            $table->integer('Radio')->nullable(); // Radio en metros para círculos
            $table->json('Coordenadas')->nullable(); // Array de coordenadas para polígonos
            $table->time('HorarioInicio')->nullable();
            $table->time('HorarioFin')->nullable();
            $table->text('Descripcion')->nullable();
            $table->enum('Estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
            
            $table->index(['Estado', 'TipoZona']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zonas');
    }
};
