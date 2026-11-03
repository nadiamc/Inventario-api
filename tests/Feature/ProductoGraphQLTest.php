<?php

namespace Tests\Feature;

use App\Events\ProductoActualizado;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProductoGraphQLTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_la_lista_de_productos(): void
    {
        Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->postJson('/graphql', [
            'query' => '{ productos { id nombre precio stock } }',
        ])->assertOk()
            ->assertJsonPath('data.productos.0.nombre', 'Teclado')
            ->assertJsonPath('data.productos.0.stock', 12)
            ->assertJsonPath('data.productos.0.precio', 25000);
    }

    public function test_consulta_un_producto_por_id(): void
    {
        $producto = Producto::create(['nombre' => 'Mouse', 'precio' => 18000, 'stock' => 20]);

        $this->postJson('/graphql', [
            'query' => "{ producto(id: {$producto->id}) { id nombre stock } }",
        ])->assertOk()
            ->assertJsonPath('data.producto.nombre', 'Mouse');
    }

    public function test_devuelve_null_si_el_producto_no_existe(): void
    {
        $this->postJson('/graphql', [
            'query' => '{ producto(id: 999) { id nombre } }',
        ])->assertOk()
            ->assertJsonPath('data.producto', null);
    }

    public function test_crea_un_producto_y_dispara_el_evento(): void
    {
        Event::fake([ProductoActualizado::class]);

        $this->postJson('/graphql', [
            'query' => 'mutation { crearProducto(nombre: "Monitor", precio: 35000, stock: 5) { id nombre stock } }',
        ])->assertOk()
            ->assertJsonPath('data.crearProducto.nombre', 'Monitor')
            ->assertJsonPath('data.crearProducto.stock', 5);

        $this->assertDatabaseHas('productos', ['nombre' => 'Monitor', 'stock' => 5]);
        Event::assertDispatched(ProductoActualizado::class);
    }

    public function test_actualiza_el_stock(): void
    {
        $producto = Producto::create(['nombre' => 'Monitor', 'precio' => 35000, 'stock' => 5]);

        $this->postJson('/graphql', [
            'query' => "mutation { actualizarStock(id: {$producto->id}, stock: 8) { id stock } }",
        ])->assertOk()
            ->assertJsonPath('data.actualizarStock.stock', 8);

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 8]);
    }

    public function test_los_errores_de_validacion_viajan_en_errors(): void
    {
        $this->postJson('/graphql', [
            'query' => 'mutation { crearProducto(nombre: "", precio: -5, stock: -1) { id } }',
        ])->assertOk()
            ->assertJsonPath('errors.0.extensions.validation.nombre.0', 'El nombre del producto es obligatorio.')
            ->assertJsonPath('errors.0.extensions.validation.precio.0', 'El precio no puede ser negativo.')
            ->assertJsonPath('errors.0.extensions.validation.stock.0', 'El stock no puede ser negativo.');
    }
}
