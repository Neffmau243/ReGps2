<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';
    protected $primaryKey = 'PermisoID';
    
    protected $fillable = [
        'Nombre',
        'Descripcion',
        'Grupo'
    ];
    
    public function roles()
    {
        return $this->hasMany(RolPermiso::class, 'PermisoID');
    }
}
