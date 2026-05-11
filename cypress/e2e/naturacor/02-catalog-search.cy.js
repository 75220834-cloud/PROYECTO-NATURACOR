/**
 * Filtros del catálogo (solo lectura / envío de formulario).
 * Comprueba que el buscador y el botón Filtrar no rompen la página.
 */
describe('NATURACOR — búsqueda en catálogo', () => {
    it('puede escribir en el buscador y enviar el formulario', () => {
        cy.visit('/catalogo');
        cy.get('#catalogoForm input[name="search"]')
            .clear()
            .type('Aloe');
        cy.get('#catalogoForm button[type="submit"]').click();
        cy.url().should('include', 'search=');
        cy.url().should('include', 'Aloe');
        cy.get('#productos').should('exist');
        cy.contains('NATURACOR', { matchCase: false }).should('be.visible');
    });

    it('puede limpiar filtros si el enlace existe', () => {
        cy.visit('/catalogo?search=test');
        cy.get('body').then(($b) => {
            if ($b.find('a.filter-clear').length) {
                cy.get('a.filter-clear').first().click();
                cy.url().should('not.include', 'search=test');
            } else {
                cy.log('Sin filtros activos: enlace Limpiar no visible (OK)');
            }
        });
    });
});
