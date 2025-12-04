<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';
    protected $primaryKey = 'DispositivoID';
    
    protected $fillable = [
        'EmpleadoID',
        'IMEI',
        'Modelo',
        'Marca',
        'Estado',
        'FechaAsignacion'
    ];
    
    protected $casts = [
        'FechaAsignacion' => 'date'
    ];
    
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'EmpleadoID');
    }
    
    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class, 'DispositivoID');
    }
    
    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'DispositivoID');
    }
}
