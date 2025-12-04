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
        Schema::create('historial_zonas', function (Blueprint $table) {
            $table->id('HistorialID');
            $table->unsignedBigInteger('ZonaID');
            $table->unsignedBigInteger('EmpleadoID');
            $table->unsignedBigInteger('DispositivoID');
            $table->enum('TipoEvento', ['Entrada', 'Salida'])->default('Entrada');
            $table->timestamp('FechaHoraEvento');
            $table->decimal('Latitud', 10, 8);
            $table->decimal('Longitud', 11, 8);
            $table->integer('TiempoPermanencia')->nullable(); // En minutos
            $table->boolean('AlertaGenerada')->default(false);
            $table->timestamps();
            
            $table->foreign('ZonaID')->references('ZonaID')->on('zonas')->onDelete('cascade');
            $table->foreign('EmpleadoID')->references('EmpleadoID')->on('empleados')->onDelete('cascade');
            $table->foreign('DispositivoID')->references('DispositivoID')->on('dispositivos')->onDelete('cascade');
            
            $table->index(['EmpleadoID', 'FechaHoraEvento']);
            $table->index(['ZonaID', 'TipoEvento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_zonas');
    }
};
