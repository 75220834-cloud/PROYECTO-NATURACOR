# Pruebas E2E NATURACOR (Cypress)

Estas pruebas **no cambian** tu código PHP ni Blade: solo abren el sitio como un navegador y comprueban pantallas y respuestas HTTP.

## Qué prueba cada archivo (`cypress/e2e/naturacor/`)

| Archivo | Para qué sirve |
|--------|----------------|
| `01-public-catalog.cy.js` | Raíz → catálogo, hero y bloque `#productos` visibles. |
| `02-catalog-search.cy.js` | Buscador y envío del formulario de filtros sin error. |
| `03-login-page.cy.js` | Página `/login` carga con email, contraseña y botón. |
| `04-login-invalid.cy.js` | Login con datos falsos → sigue en `/login` y aparece `.error-msg`. |
| `05-login-session-admin.cy.js` | **Opcional:** login admin + dashboard y `/reportes` (requiere credenciales). |
| `06-login-session-empleado-pos.cy.js` | **Opcional:** login empleado + pantalla POS (requiere credenciales). |
| `07-health-up.cy.js` | `GET /up` devuelve 200 (comprobación rápida del servicio). |

## Cómo ejecutarlas (local)

1. Arranca Laravel (en otra terminal):

   `php artisan serve`

   Por defecto Cypress usa `http://127.0.0.1:8000`.

2. Instala dependencias (una vez):

   `npm install`

3. Modo interactivo (ver el navegador):

   `npm run cypress:open`

   Elige **E2E** y un spec de `naturacor/`.

4. Modo consola (CI o rápido):

   `npm run cypress:run`

## Probar contra otra URL (p. ej. producción)

No es obligatorio; si lo haces, usa una cuenta de prueba y evita crear datos innecesarios.

PowerShell:

```powershell
$env:CYPRESS_BASE_URL="https://naturacor.com"
npm run cypress:run
```

## Credenciales opcionales (admin / empleado)

Sin ellas, los bloques 05 y 06 se **omit** (no fallan).

**Opción A — variables de entorno** (PowerShell):

```powershell
$env:CYPRESS_ADMIN_EMAIL="tu@correo.com"
$env:CYPRESS_ADMIN_PASSWORD="tu_clave"
$env:CYPRESS_EMPLEADO_EMAIL="empleado@..."
$env:CYPRESS_EMPLEADO_PASSWORD="..."
npm run cypress:run
```

**Opción B — archivo** `cypress.env.json` en la raíz del proyecto (está en `.gitignore`; no lo subas a Git):

```json
{
  "ADMIN_EMAIL": "admin@naturacor.com",
  "ADMIN_PASSWORD": "solo-entorno-local",
  "EMPLEADO_EMAIL": "empleado@naturacor.com",
  "EMPLEADO_PASSWORD": "solo-entorno-local"
}
```

## Railway / `git push`

El despliegue habitual solo necesita `npm run build` (Vite). Cypress queda como **dependencia de desarrollo**; si Railway no ejecuta `npm run cypress:run`, **no afecta** el sitio en producción.

Los ejemplos oficiales de Cypress siguen en `cypress/e2e/1-getting-started` y `2-advanced-examples`, pero **no se ejecutan** con `npm run cypress:run` (solo corre `cypress/e2e/naturacor/`).
