<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoInternoTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'clave-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.grpc.internal_key' => self::CLAVE]);
    }

    public function test_rechaza_la_peticion_sin_cabecera(): void
    {
        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->getJson("/api/interno/productos/{$producto->id}")->assertForbidden();
    }

    public function test_rechaza_una_clave_incorrecta(): void
    {
        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->getJson("/api/interno/productos/{$producto->id}", ['X-Internal-Key' => 'otra-clave'])
            ->assertForbidden();
    }

    public function test_rechaza_todo_si_no_hay_clave_configurada(): void
    {
        config(['services.grpc.internal_key' => '']);

        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->getJson("/api/interno/productos/{$producto->id}", ['X-Internal-Key' => ''])
            ->assertForbidden();
    }

    public function test_devuelve_el_producto_con_la_clave_correcta(): void
    {
        $producto = Producto::create(['nombre' => 'Teclado', 'precio' => 25000, 'stock' => 12]);

        $this->getJson("/api/interno/productos/{$producto->id}", ['X-Internal-Key' => self::CLAVE])
            ->assertOk()
            ->assertJsonPath('nombre', 'Teclado')
            ->assertJsonPath('stock', 12);
    }

    public function test_devuelve_404_si_el_producto_no_existe(): void
    {
        $this->getJson('/api/interno/productos/999', ['X-Internal-Key' => self::CLAVE])
            ->assertNotFound();
    }
}
