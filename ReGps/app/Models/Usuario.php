<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    
    protected $table = 'usuarios';
    protected $primaryKey = 'UsuarioID';
    
    protected $fillable = [
        'Nombre',
        'Email',
        'Contraseña',
        'Rol',
        'Estado',
        'remember_token',
        'last_login_at',
        'last_login_ip'
    ];
    
    protected $hidden = [
        'Contraseña',
        'remember_token'
    ];
    
    protected $casts = [
        'last_login_at' => 'datetime',
    ];
    
    // Override para usar 'Contraseña' en lugar de 'password'
    public function getAuthPassword()
    {
        return $this->Contraseña;
    }
    
    // Mutator para hashear contraseña automáticamente
    public function setContraseñaAttribute($value)
    {
        $this->attributes['Contraseña'] = Hash::make($value);
    }
    
    // Verificar si el usuario tiene un permiso
    public function tienePermiso($permiso)
    {
        return Permiso::whereHas('roles', function($query) {
            $query->where('Rol', $this->Rol);
        })->where('Nombre', $permiso)->exists();
    }
    
    // Verificar si es administrador
    public function esAdministrador()
    {
        return $this->Rol === 'Administrador';
    }
    
    // Relaciones
    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'UsuarioID');
    }
    
    public function permisos()
    {
        return $this->hasManyThrough(
            Permiso::class,
            RolPermiso::class,
            'Rol',
            'PermisoID',
            'Rol',
            'PermisoID'
        );
    }
}
