# Flujo de trabajo con Git y Pull Requests

Guía corta para este repositorio. La idea: `main` siempre funciona y cada
cambio entra por una rama y un Pull Request (PR).

## Ramas

| Rama | Uso |
|---|---|
| `main` | Código estable. No se trabaja directamente aquí. |
| `feat/...` | Nueva funcionalidad (`feat/busqueda-productos`). |
| `fix/...` | Corrección de un error (`fix/stock-negativo`). |
| `docs/...` | Documentación (`docs/guia-grpc`). |
| `test/...` | Solo pruebas (`test/cobertura-graphql`). |
| `chore/...` | Tareas de mantenimiento (`chore/actualiza-dependencias`). |

Usa minúsculas, guiones y un nombre corto que describa el cambio.

## 1. Pon `main` al día

```sh
git switch main
git pull --ff-only
```

## 2. Crea la rama

```sh
git switch -c feat/busqueda-productos
```

## 3. Trabaja y verifica

Antes de cada commit:

```sh
php artisan test
php vendor/bin/pint --test
```

Si Pint falla, corrige el formato con `php vendor/bin/pint`.

## 4. Haz commits pequeños y explicados

```sh
git add -A
git commit -m "feat(productos): añade búsqueda por nombre" -m "Permite filtrar productos por coincidencia parcial, reutilizando ProductoService para no duplicar la validación."
```

Formato del mensaje:

```
tipo(alcance): resumen en imperativo, máximo 72 caracteres

Por qué se hace el cambio, qué se tuvo en cuenta y cualquier efecto
secundario. Un commit debe ser una unidad lógica de trabajo.
```

Tipos permitidos:

| Tipo | Cuándo |
|---|---|
| `feat` | Funcionalidad nueva |
| `fix` | Corrección de un error |
| `docs` | Documentación |
| `test` | Pruebas |
| `refactor` | Reorganización sin cambiar comportamiento |
| `perf` | Mejora de rendimiento |
| `chore` | Configuración, dependencias, herramientas |

Alcances usados en este proyecto: `productos`, `api-rest`, `graphql`,
`realtime`, `grpc`, `i18n`, `docs`.

Buenas prácticas:

- Un commit = un cambio lógico. Si el mensaje necesita "y", probablemente son dos.
- Explica el **porqué**, no repitas el diff.
- No mezcles formato, refactor y funcionalidad en el mismo commit.
- Nunca subas `.env`, bases de datos locales ni claves.

## 5. Sube la rama

```sh
git push -u origin feat/busqueda-productos
```

## 6. Abre el Pull Request en la web

GitHub te muestra un botón **Compare & pull request** al entrar al repo.
También puedes abrirlo directamente con esta URL:

```
https://github.com/nadiamc/Inventario-api/compare/main...feat/busqueda-productos?expand=1
```

En la descripción incluye:

```markdown
## Qué cambia
- ...

## Por qué
...

## Cómo probarlo
1. php artisan test
2. curl ...
```

## 7. Revisa y fusiona

- Revisa la pestaña **Files changed**.
- Si algo falla, haz otro commit en la misma rama: el PR se actualiza solo.
- Usa **Squash and merge** para que `main` reciba un único commit limpio.
- Borra la rama con **Delete branch** desde el PR.

## 8. Vuelve a `main`

```sh
git switch main
git pull --ff-only
git branch -d feat/busqueda-productos
```

## Si `main` avanzó mientras trabajabas

```sh
git switch feat/busqueda-productos
git fetch origin
git rebase origin/main
# resuelve conflictos si aparecen
git push --force-with-lease
```

## Problemas típicos

| Problema | Solución |
|---|---|
| `push` rechazado (non-fast-forward) | `git pull --rebase origin main` y vuelve a subir. |
| Hice commits en `main` por error | `git switch -c feat/mi-cambio` y luego `git branch -f main origin/main`. |
| Quiero deshacer el último commit sin perder los archivos | `git reset --soft HEAD~1`. |
| Necesito ver qué voy a subir | `git status` y `git diff --staged`. |

## Checklist antes de abrir un PR

- [ ] `php artisan test` en verde.
- [ ] `php vendor/bin/pint --test` sin errores.
- [ ] `.env` y archivos locales no están en `git status`.
- [ ] El título del PR describe el cambio y el cuerpo explica cómo probarlo.
- [ ] La rama tiene un nombre claro y el PR apunta a `main`.
