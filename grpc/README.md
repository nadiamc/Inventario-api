# Servicio gRPC de inventario

Node.js actúa como servidor gRPC y consulta el endpoint interno de Laravel
(`GET /api/interno/productos/{id}`) usando la clave compartida `GRPC_INTERNAL_KEY`.

## Instalación

```sh
cd grpc
npm install
```

## Ejecutar (3 terminales)

```powershell
# 1) Laravel (desde la raíz del proyecto)
php artisan serve

# 2) Servidor gRPC
cd grpc
$env:GRPC_INTERNAL_KEY='clave-local-larga-de-prueba'
node server.cjs

# 3) Clientes
node client.cjs 1
python client.py 1
```

En Linux/macOS el servidor se arranca con:

```sh
GRPC_INTERNAL_KEY=clave-local-larga-de-prueba node server.cjs
```

## Stubs de Python

```sh
cd grpc
python -m pip install grpcio grpcio-tools
python -m grpc_tools.protoc -I. --python_out=. --grpc_python_out=. producto.proto
```

## Notas

- gRPC usa HTTP/2 sin TLS solo para laboratorio local; no exponer el puerto 50051.
- Si el producto no existe, Laravel responde 404 y el servidor gRPC devuelve `NOT_FOUND` (código 5).
- Si la clave es incorrecta, Laravel responde 403 y el servidor gRPC devuelve `PERMISSION_DENIED` (código 7).
