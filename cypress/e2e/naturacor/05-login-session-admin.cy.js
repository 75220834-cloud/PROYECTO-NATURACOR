/**
 * Sesión admin (opcional): requiere variables de entorno o cypress.env.json
 *   ADMIN_EMAIL, ADMIN_PASSWORD
 *
 * Si no están definidas, el bloque se omite (no falla en CI sin secretos).
 * Útil en local con el mismo usuario que en AdminSeeder (solo si existe en esa BD).
 */
describe('NATURACOR — sesión administrador', () => {
    const email = Cypress.env('ADMIN_EMAIL');
    const password = Cypress.env('ADMIN_PASSWORD');

    before(function () {
        if (!email || !password) {
            cy.log('Omitido: define CYPRESS_ADMIN_EMAIL y CYPRESS_ADMIN_PASSWORD (o cypress.env.json) para ejecutar este bloque.');
            this.skip();
        }
    });

    it('login admin y acceso al dashboard', () => {
        cy.naturacorLogin(email, password);
        cy.url({ timeout: 25000 }).should('include', '/dashboard');
        cy.contains(/logged in|sesión|Dashboard/i).should('exist');
    });

    it('admin puede abrir reportes (solo GET)', () => {
        if (!email || !password) {
            return;
        }
        cy.naturacorLogin(email, password);
        cy.visit('/reportes');
        cy.url().should('include', '/reportes');
        cy.get('body').should('be.visible');
    });
});
