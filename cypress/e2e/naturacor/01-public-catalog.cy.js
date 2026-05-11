/**
 * Flujo público: raíz y catálogo (sin login).
 * Sirve para comprobar que el sitio responde y el catálogo carga para visitantes.
 */
describe('NATURACOR — catálogo público', () => {
    it('la raíz redirige al catálogo', () => {
        cy.visit('/');
        cy.location('pathname', { timeout: 20000 }).should('eq', '/catalogo');
    });

    it('muestra el catálogo con hero y zona de productos', () => {
        cy.visit('/catalogo');
        cy.contains('NATURACOR', { matchCase: false }).should('be.visible');
        cy.get('#productos').should('exist');
        cy.get('#catalogoForm').should('exist');
    });

    it('el formulario de filtros tiene búsqueda y categorías', () => {
        cy.visit('/catalogo');
        cy.get('#catalogoForm input[name="search"]').should('exist');
        cy.get('#catalogoForm input[name="tipo"]').should('have.length.at.least', 1);
    });
});
