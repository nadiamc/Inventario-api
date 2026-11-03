<?php

namespace App\Events;

use App\Models\Producto;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ProductoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @var array<string, mixed>
     */
    public array $producto;

    public function __construct(Producto $producto, public string $accion)
    {
        $this->producto = $producto->only('id', 'nombre', 'precio', 'stock');
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('inventario')];
    }

    public function broadcastAs(): string
    {
        return 'producto.actualizado';
    }
}
