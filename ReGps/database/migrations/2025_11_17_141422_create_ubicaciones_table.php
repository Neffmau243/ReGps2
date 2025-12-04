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
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id('UbicacionID');
            $table->unsignedBigInteger('DispositivoID');
            $table->decimal('Latitud', 10, 8);
            $table->decimal('Longitud', 11, 8);
            $table->decimal('Velocidad', 5, 2)->nullable();
            $table->string('Direccion', 255)->nullable();
            $table->timestamp('FechaHora');
            $table->timestamps();
            
            $table->foreign('DispositivoID')->references('DispositivoID')->on('dispositivos')->onDelete('cascade');
            $table->index(['DispositivoID', 'FechaHora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
