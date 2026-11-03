<?php

use App\Http\Controllers\Api\ProductoController;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('productos', ProductoController::class);

/*
|--------------------------------------------------------------------------
| Endpoint interno para el servicio gRPC (solo red local)
|--------------------------------------------------------------------------
|
| Se protege con una clave compartida en la cabecera X-Internal-Key.
| La clave se lee desde config para que siga funcionando con config:cache.
|
*/
Route::get('/interno/productos/{producto}', function (Request $request, Producto $producto) {
    $clave = (string) config('services.grpc.internal_key');

    abort_if(
        $clave === '' || ! hash_equals($clave, (string) $request->header('X-Internal-Key')),
        403,
        'Clave interna inválida.'
    );

    return response()->json($producto);
});
