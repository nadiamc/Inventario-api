# Inventario API multicanal

Laboratorio de APIs con **Laravel 13**: un mismo dominio (`ProductoService`) expuesto por **REST**, **GraphQL**, **WebSockets (Reverb)** y **gRPC** (servidor Node.js que consulta un endpoint interno protegido de Laravel).

## Arquitectura

```
                    ┌──────────────────────────┐
  REST (JSON) ─────▶│                          │
                    │    ProductoService       │──▶ Eloquent ──▶ SQLite
  GraphQL ─────────▶│  (validación + eventos)  │
                    └──────────┬───────────────┘
                               │ event(ProductoActualizado)
                               ▼
                        Reverb (WebSocket) ──▶ Echo (navegador)
                               ▲
  Cliente gRPC ──▶ Node.js ────┘  GET /api/interno/productos/{id}
      (50051)      (HTTP + X-Internal-Key)
```

- `app/Services/ProductoService.php`: única fuente de verdad (validaciones, CRUD y emisión de eventos).
- `app/Events/ProductoActualizado.php`: `ShouldBroadcast` en el canal público `inventario`, alias `producto.actualizado`.
- `app/Http/Controllers/Api/ProductoController.php`: capa REST.
- `app/GraphQL/Mutations/ProductoMutator.php`: mutaciones GraphQL.
- `grpc/`: servidor y clientes gRPC.

## Requisitos

- PHP 8.3+ y Composer
- Node.js 20+ y npm
- SQLite (por defecto) o MySQL
- Python 3.10+ y `grpcio` / `grpcio-tools` (solo cliente Python opcional)

## Instalación

```sh
composer install
npm install
cp .env.example .env        # PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan reverb:install   # genera las credenciales REVERB_* en .env
npm run build
```

`.env` relevante:

```dotenv
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
DB_CONNECTION=sqlite

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

GRPC_INTERNAL_KEY=clave-local-larga-de-prueba
```

## Ejecutar el proyecto

```sh
# Terminal 1 - API + vistas
php artisan serve

# Terminal 2 - WebSockets
php artisan reverb:start --debug

# Terminal 3 - cola (los eventos ShouldBroadcast se encolan)
php artisan queue:work

# Terminal 4 - Vite en modo dev (o usar `npm run build` una vez)
npm run dev
```

Vista en tiempo real: <http://127.0.0.1:8000/inventario> (ábrela en dos pestañas y pulsa `+1 stock`).

## REST

| Método | Ruta | Respuesta |
|---|---|---|
| GET | `/api/productos` | `200` listado |
| POST | `/api/productos` | `201` creado |
| GET | `/api/productos/{id}` | `200` / `404` |
| PUT/PATCH | `/api/productos/{id}` | `200` / `404` / `422` |
| DELETE | `/api/productos/{id}` | `204` |

```sh
curl -i http://127.0.0.1:8000/api/productos
curl -i -X POST http://127.0.0.1:8000/api/productos \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d "{\"nombre\": \"Teclado\", \"precio\": 25000, \"stock\": 12}"
```

## GraphQL

Endpoint: `POST /graphql` (Lighthouse). Schema en `graphql/schema.graphql`.

```sh
curl -X POST http://127.0.0.1:8000/graphql \
  -H "Content-Type: application/json" \
  -d "{\"query\": \"{ productos { id nombre precio stock } }\"}"
```

Operaciones disponibles:

```graphql
{ productos { id nombre precio stock } }
{ producto(id: 1) { id nombre } }

mutation { crearProducto(nombre: "Mouse", precio: 18000, stock: 20) { id nombre stock } }
mutation { actualizarStock(id: 1, stock: 8) { id stock } }
```

Los errores de validación llegan con HTTP `200` y el arreglo `errors[].extensions.validation`.

## WebSockets (Reverb)

- Canal público: `inventario`
- Evento: `.producto.actualizado`
- Payload: `{ "producto": { "id": 1, "nombre": "Teclado", "precio": "25000.00", "stock": 13 }, "accion": "actualizado" }`

Si no se reciben eventos: revisar `VITE_REVERB_*`, recompilar assets y que `reverb:start` y `queue:work` estén activos.

## gRPC

Detalle en [`grpc/README.md`](grpc/README.md).

```sh
cd grpc
npm install
$env:GRPC_INTERNAL_KEY='clave-local-larga-de-prueba'; node server.cjs   # PowerShell
node client.cjs 1
python client.py 1
```

| Caso | Código gRPC |
|---|---|
| Producto inexistente | `NOT_FOUND` (5) |
| Clave interna incorrecta | `PERMISSION_DENIED` (7) |
| Laravel caído | `UNAVAILABLE` (14) |

> HTTP/2 sin TLS solo para laboratorio local: no exponer el puerto `50051`.

## Pruebas

```sh
php artisan test
php vendor/bin/pint --test   # estilo de código
```

Cobertura de la suite:

- `tests/Feature/ProductoRestTest.php`: CRUD, `422`, `404`, `204` y evento.
- `tests/Feature/ProductoGraphQLTest.php`: queries, mutaciones y errores de validación.
- `tests/Feature/ProductoInternoTest.php`: endpoint interno con/sin `X-Internal-Key`.
- `tests/Unit/ProductoServiceTest.php`: reglas de negocio y eventos.

## Contribuir

El flujo de ramas, mensajes de commit y Pull Requests está en
[`CONTRIBUTING.md`](CONTRIBUTING.md). Resumen: `main` siempre estable,
una rama por cambio (`feat/...`, `fix/...`, `docs/...`) y PR con
**Squash and merge**.

## Notas de producción

- Sustituir la clave compartida del endpoint interno por autenticación robusta y red privada (o mover el acceso a `config` + servicio interno).
- Los canales de broadcasting del ejemplo son públicos: autenticar y proteger canales si se exponen datos reales.
- El botón `+1 stock` es demostrativo; para descuentos concurrentes usar transacciones y bloqueo de filas o actualización atómica.
- `NOT_FOUND` y validaciones se traducen al español mediante `lang/es/validation.php` y `APP_LOCALE=es`.
