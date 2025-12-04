<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $table = 'alertas';
    protected $primaryKey = 'AlertaID';
    
    protected $fillable = [
        'DispositivoID',
        'TipoAlerta',
        'Prioridad',
        'Descripcion',
        'FechaHora',
        'Estado',
        'AsignadoA',
        'Notas'
    ];
    
    protected $casts = [
        'FechaHora' => 'datetime'
    ];
    
    public function dispositivo()
    {
        return $this->belongsTo(Dispositivo::class, 'DispositivoID');
    }
    
    public function asignado()
    {
        return $this->belongsTo(Usuario::class, 'AsignadoA', 'UsuarioID');
    }
}
