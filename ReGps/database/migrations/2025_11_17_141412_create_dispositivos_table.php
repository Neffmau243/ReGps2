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
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id('DispositivoID');
            $table->unsignedBigInteger('EmpleadoID')->nullable();
            $table->string('IMEI', 50)->unique();
            $table->string('Modelo', 100)->nullable();
            $table->string('Marca', 100)->nullable();
            $table->enum('Estado', ['Activo', 'Inactivo', 'Mantenimiento'])->default('Activo');
            $table->date('FechaAsignacion')->nullable();
            $table->timestamps();
            
            $table->foreign('EmpleadoID')->references('EmpleadoID')->on('empleados')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};
