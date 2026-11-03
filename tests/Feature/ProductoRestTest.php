<?php

namespace Tests\Feature;

use App\Events\ProductoActualizado;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProductoRestTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_los_productos(): void
    {
        Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);
        Producto::create(['nombre' => 'Mouse', 'precio' => 18000, 'stock' => 20]);

        $this->getJson('/api/productos')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.nombre', 'Teclado');
    }

    public function test_crea_un_producto_y_dispara_el_evento(): void
    {
        Event::fake([ProductoActualizado::class]);

        $respuesta = $this->postJson('/api/productos', [
            'nombre' => 'Monitor',
            'precio' => 35000,
            'stock' => 5,
        ]);

        $respuesta->assertCreated()
            ->assertJsonPath('nombre', 'Monitor')
            ->assertJsonPath('stock', 5);

        $this->assertDatabaseHas('productos', ['nombre' => 'Monitor']);

        Event::assertDispatched(ProductoActualizado::class, function (ProductoActualizado $evento) {
            return $evento->accion === 'creado'
                && $evento->producto['nombre'] === 'Monitor';
        });
    }

    public function test_la_validacion_devuelve_422_en_espanol(): void
    {
        $this->postJson('/api/productos', [
            'nombre' => '',
            'precio' => -1,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'precio', 'stock'])
            ->assertJsonPath('errors.nombre.0', 'El nombre del producto es obligatorio.')
            ->assertJsonPath('errors.precio.0', 'El precio no puede ser negativo.');
    }

    public function test_muestra_un_producto(): void
    {
        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->getJson("/api/productos/{$producto->id}")
            ->assertOk()
            ->assertJsonPath('nombre', 'Teclado');
    }

    public function test_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/productos/999')->assertNotFound();
    }

    public function test_actualiza_parcialmente_el_stock(): void
    {
        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->patchJson("/api/productos/{$producto->id}", ['stock' => 11])
            ->assertOk()
            ->assertJsonPath('stock', 11);

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 11, 'nombre' => 'Teclado']);
    }

    public function test_elimina_un_producto_y_devuelve_204(): void
    {
        Event::fake([ProductoActualizado::class]);

        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->deleteJson("/api/productos/{$producto->id}")->assertNoContent();

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
        Event::assertDispatched(ProductoActualizado::class, fn (ProductoActualizado $e) => $e->accion === 'eliminado');
    }
}
