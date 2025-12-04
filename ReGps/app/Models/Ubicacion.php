<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';
    protected $primaryKey = 'UbicacionID';
    
    protected $fillable = [
        'DispositivoID',
        'Latitud',
        'Longitud',
        'Velocidad',
        'Direccion',
        'FechaHora'
    ];
    
    protected $casts = [
        'FechaHora' => 'datetime',
        'Latitud' => 'decimal:8',
        'Longitud' => 'decimal:8',
        'Velocidad' => 'decimal:2'
    ];
    
    public function dispositivo()
    {
        return $this->belongsTo(Dispositivo::class, 'DispositivoID');
    }
}
