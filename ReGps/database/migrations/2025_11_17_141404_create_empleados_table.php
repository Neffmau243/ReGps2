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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id('EmpleadoID');
            $table->unsignedBigInteger('UsuarioID');
            $table->string('Nombre', 100);
            $table->string('Apellido', 100);
            $table->string('Telefono', 20)->nullable();
            $table->string('Direccion', 255)->nullable();
            $table->enum('Estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
            
            $table->foreign('UsuarioID')->references('UsuarioID')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
