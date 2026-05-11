/**
 * Empleado: login y carga del POS (solo pantalla, no registra ventas).
 * Requiere EMPLEADO_EMAIL y EMPLEADO_PASSWORD en env / cypress.env.json
 */
describe('NATURACOR — empleado y POS', () => {
    const email = Cypress.env('EMPLEADO_EMAIL');
    const password = Cypress.env('EMPLEADO_PASSWORD');

    before(function () {
        if (!email || !password) {
            cy.log('Omitido: define CYPRESS_EMPLEADO_EMAIL y CYPRESS_EMPLEADO_PASSWORD para este bloque.');
            this.skip();
        }
    });

    it('empleado accede al punto de venta', () => {
        cy.naturacorLogin(email, password);
        cy.visit('/ventas/pos');
        cy.url().should('include', '/ventas/pos');
        cy.get('body').should('be.visible');
    });
});
