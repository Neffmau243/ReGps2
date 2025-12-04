<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    protected $table = 'zonas';
    protected $primaryKey = 'ZonaID';
    
    protected $fillable = [
        'Nombre',
        'TipoZona',
        'TipoGeometria',
        'Latitud',
        'Longitud',
        'Radio',
        'Coordenadas',
        'HorarioInicio',
        'HorarioFin',
        'Descripcion',
        'Estado'
    ];
    
    protected $casts = [
        'Coordenadas' => 'array',
        'Latitud' => 'decimal:8',
        'Longitud' => 'decimal:8',
        'Radio' => 'integer'
    ];
    
    public function historial()
    {
        return $this->hasMany(HistorialZona::class, 'ZonaID');
    }
    
    /**
     * Verifica si un punto está dentro de la zona
     */
    public function contienePunto($latitud, $longitud): bool
    {
        if ($this->TipoGeometria === 'Circulo') {
            return $this->puntoEnCirculo($latitud, $longitud);
        }
        
        return $this->puntoEnPoligono($latitud, $longitud);
    }
    
    /**
     * Calcula si un punto está dentro de un círculo
     */
    private function puntoEnCirculo($lat, $lng): bool
    {
        $radioTierra = 6371000; // metros
        
        $dLat = deg2rad($lat - $this->Latitud);
        $dLng = deg2rad($lng - $this->Longitud);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($this->Latitud)) * cos(deg2rad($lat)) *
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distancia = $radioTierra * $c;
        
        return $distancia <= $this->Radio;
    }
    
    /**
     * Algoritmo Ray Casting para verificar si un punto está en un polígono
     */
    private function puntoEnPoligono($lat, $lng): bool
    {
        if (!$this->Coordenadas || count($this->Coordenadas) < 3) {
            return false;
        }
        
        $vertices = $this->Coordenadas;
        $dentro = false;
        
        for ($i = 0, $j = count($vertices) - 1; $i < count($vertices); $j = $i++) {
            $xi = $vertices[$i]['lat'];
            $yi = $vertices[$i]['lng'];
            $xj = $vertices[$j]['lat'];
            $yj = $vertices[$j]['lng'];
            
            $intersect = (($yi > $lng) != ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);
            
            if ($intersect) {
                $dentro = !$dentro;
            }
        }
        
        return $dentro;
    }
}
