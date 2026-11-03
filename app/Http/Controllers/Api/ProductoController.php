<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductoController extends Controller
{
    public function __construct(private ProductoService $servicio) {}

    public function index(): JsonResponse
    {
        return response()->json($this->servicio->listar(), 200);
    }

    public function store(Request $request): JsonResponse
    {
        $producto = $this->servicio->crear($request->all());

        return response()->json($producto, 201);
    }

    public function show(Producto $producto): JsonResponse
    {
        return response()->json($producto, 200);
    }

    public function update(Request $request, Producto $producto): JsonResponse
    {
        return response()->json(
            $this->servicio->actualizar($producto, $request->all()),
            200
        );
    }

    public function destroy(Producto $producto): Response
    {
        $this->servicio->eliminar($producto);

        return response()->noContent();
    }
}
