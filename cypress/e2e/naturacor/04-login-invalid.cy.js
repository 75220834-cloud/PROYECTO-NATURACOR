/**
 * Credenciales inválidas: el sistema debe rechazar el login sin entrar al dashboard.
 * No usa cuentas reales; solo email/contraseña ficticios.
 */
describe('NATURACOR — login inválido', () => {
    it('rechaza credenciales incorrectas y muestra error', () => {
        cy.visit('/login');
        cy.get('#email').clear().type('noexiste-naturacor-e2e@example.invalid');
        cy.get('#password').clear().type('ClaveIncorrecta123!XYZ');
        cy.get('#btnLogin').click();
        cy.url().should('include', '/login');
        cy.get('.error-msg').should('be.visible');
    });
});
