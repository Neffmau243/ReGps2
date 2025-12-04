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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id('AlertaID');
            $table->unsignedBigInteger('DispositivoID');
            $table->enum('TipoAlerta', ['Velocidad', 'Zona', 'Bateria', 'Desconexion'])->default('Velocidad');
            $table->text('Descripcion')->nullable();
            $table->timestamp('FechaHora');
            $table->enum('Estado', ['Pendiente', 'Revisada', 'Resuelta'])->default('Pendiente');
            $table->timestamps();
            
            $table->foreign('DispositivoID')->references('DispositivoID')->on('dispositivos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
