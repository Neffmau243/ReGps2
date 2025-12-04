<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'EmpleadoID';
    
    protected $fillable = [
        'UsuarioID',
        'Nombre',
        'Apellido',
        'Telefono',
        'Direccion',
        'Estado'
    ];
    
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'UsuarioID');
    }
    
    public function dispositivos()
    {
        return $this->hasMany(Dispositivo::class, 'EmpleadoID');
    }
}
