<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de permisos
        Schema::create('permisos', function (Blueprint $table) {
            $table->id('PermisoID');
            $table->string('Nombre', 100)->unique();
            $table->string('Descripcion', 255)->nullable();
            $table->string('Grupo', 50)->nullable(); // usuarios, empleados, dispositivos, etc.
            $table->timestamps();
        });

        // Tabla pivote: roles tienen permisos
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->id();
            $table->enum('Rol', ['Administrador', 'Empleado']);
            $table->foreignId('PermisoID')->constrained('permisos', 'PermisoID')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['Rol', 'PermisoID']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('permisos');
    }
};
