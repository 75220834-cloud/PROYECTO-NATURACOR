import { defineConfig } from 'cypress';

/**
 * E2E NATURACOR — no modifica la app Laravel; solo visita URLs.
 *
 * URL base (local o producción):
 *   set CYPRESS_BASE_URL=https://naturacor.com   (PowerShell: $env:CYPRESS_BASE_URL="...")
 *   npm run cypress:run
 *
 * Credenciales opcionales (solo tests de sesión):
 *   CYPRESS_ADMIN_EMAIL   CYPRESS_ADMIN_PASSWORD
 *   CYPRESS_EMPLEADO_EMAIL CYPRESS_EMPLEADO_PASSWORD
 */
export default defineConfig({
    e2e: {
        baseUrl: process.env.CYPRESS_BASE_URL || 'http://127.0.0.1:8000',
        specPattern: 'cypress/e2e/naturacor/**/*.cy.js',
        supportFile: 'cypress/support/e2e.js',
        video: false,
        screenshotOnRunFailure: true,
        defaultCommandTimeout: 15000,
        setupNodeEvents() {},
    },
});
