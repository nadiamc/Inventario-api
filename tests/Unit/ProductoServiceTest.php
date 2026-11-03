<?php

namespace Tests\Unit;

use App\Events\ProductoActualizado;
use App\Services\ProductoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductoServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = app(ProductoService::class);
    }

    public function test_crea_un_producto_validando_los_datos(): void
    {
        Event::fake([ProductoActualizado::class]);

        $producto = $this->servicio->crear([
            'nombre' => 'Teclado',
            'precio' => 25000,
            'stock' => 12,
            'campo_extra' => 'ignorado',
        ]);

        $this->assertSame('Teclado', $producto->nombre);
        $this->assertSame(12, $producto->stock);
        $this->assertDatabaseHas('productos', ['nombre' => 'Teclado']);

        Event::assertDispatched(ProductoActualizado::class, function (ProductoActualizado $evento) use ($producto) {
            return $evento->accion === 'creado'
                && $evento->producto['id'] === $producto->id
                && ! array_key_exists('campo_extra', $evento->producto);
        });
    }

    public function test_rechaza_datos_invalidos(): void
    {
        $this->expectException(ValidationException::class);

        $this->servicio->crear(['nombre' => '', 'precio' => -1, 'stock' => -1]);
    }

    public function test_actualiza_solo_los_campos_enviados(): void
    {
        Event::fake([ProductoActualizado::class]);

        $producto = $this->servicio->crear(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $actualizado = $this->servicio->actualizar($producto, ['stock' => 10]);

        $this->assertSame(10, $actualizado->stock);
        $this->assertSame('Teclado', $actualizado->nombre);

        Event::assertDispatched(ProductoActualizado::class, fn (ProductoActualizado $e) => $e->accion === 'actualizado');
    }

    public function test_cambia_el_stock(): void
    {
        $producto = $this->servicio->crear(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->assertSame(3, $this->servicio->cambiarStock($producto, 3)->stock);
    }

    public function test_busca_un_producto_inexistente(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->servicio->buscar(999);
    }

    public function test_elimina_un_producto(): void
    {
        Event::fake([ProductoActualizado::class]);

        $producto = $this->servicio->crear(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->servicio->eliminar($producto);

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
        Event::assertDispatched(ProductoActualizado::class, fn (ProductoActualizado $e) => $e->accion === 'eliminado');
    }
}
