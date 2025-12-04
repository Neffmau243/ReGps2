<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialZona extends Model
{
    protected $table = 'historial_zonas';
    protected $primaryKey = 'HistorialID';
    
    protected $fillable = [
        'ZonaID',
        'EmpleadoID',
        'DispositivoID',
        'TipoEvento',
        'FechaHoraEvento',
        'Latitud',
        'Longitud',
        'TiempoPermanencia',
        'AlertaGenerada'
    ];
    
    protected $casts = [
        'FechaHoraEvento' => 'datetime',
        'Latitud' => 'decimal:8',
        'Longitud' => 'decimal:8',
        'AlertaGenerada' => 'boolean'
    ];
    
    public function zona()
    {
        return $this->belongsTo(Zona::class, 'ZonaID');
    }
    
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'EmpleadoID');
    }
    
    public function dispositivo()
    {
        return $this->belongsTo(Dispositivo::class, 'DispositivoID');
    }
}
