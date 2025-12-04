<?php

namespace App\Events;

use App\Models\Ubicacion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UbicacionActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ubicacion;

    /**
     * Create a new event instance.
     */
    public function __construct(Ubicacion $ubicacion)
    {
        $this->ubicacion = $ubicacion->load('dispositivo.empleado');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('ubicaciones'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->ubicacion->UbicacionID,
            'dispositivo_id' => $this->ubicacion->DispositivoID,
            'latitud' => $this->ubicacion->Latitud,
            'longitud' => $this->ubicacion->Longitud,
            'velocidad' => $this->ubicacion->Velocidad,
            'direccion' => $this->ubicacion->Direccion,
            'precision' => $this->ubicacion->Precision,
            'timestamp' => $this->ubicacion->Timestamp,
            'dispositivo' => [
                'modelo' => $this->ubicacion->dispositivo->Modelo ?? null,
                'imei' => $this->ubicacion->dispositivo->IMEI ?? null,
                'empleado' => $this->ubicacion->dispositivo->empleado ? [
                    'nombre' => $this->ubicacion->dispositivo->empleado->Nombre,
                    'apellido' => $this->ubicacion->dispositivo->empleado->Apellido,
                ] : null,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ubicacion.actualizada';
    }
}
