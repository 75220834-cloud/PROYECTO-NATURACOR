// Comandos reutilizables para E2E NATURACOR (solo UI, sin tocar el backend).

/**
 * Inicia sesión por el formulario web (igual que un usuario humano).
 * No guarda contraseñas en logs de Cypress (log: false en el password).
 */
Cypress.Commands.add('naturacorLogin', (email, password) => {
    cy.visit('/login');
    cy.get('#email').should('be.visible').clear().type(email);
    cy.get('#password').should('be.visible').clear().type(password, { log: false });
    cy.get('#btnLogin').should('be.visible').click();
});
