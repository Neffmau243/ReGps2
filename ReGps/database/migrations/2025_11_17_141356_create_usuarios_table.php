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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('UsuarioID');
            $table->string('Nombre', 100);
            $table->string('Email', 100)->unique();
            $table->string('Contraseña', 255);
            $table->enum('Rol', ['Administrador', 'Empleado'])->default('Empleado');
            $table->enum('Estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
