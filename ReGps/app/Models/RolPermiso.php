<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    protected $table = 'rol_permiso';
    
    protected $fillable = [
        'Rol',
        'PermisoID'
    ];
    
    public function permiso()
    {
        return $this->belongsTo(Permiso::class, 'PermisoID');
    }
}
