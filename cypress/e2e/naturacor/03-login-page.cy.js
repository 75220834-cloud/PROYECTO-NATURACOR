/**
 * Página de login accesible sin autenticación.
 * No envía credenciales reales; solo valida que el formulario existe.
 */
describe('NATURACOR — pantalla de login', () => {
    it('muestra el formulario de acceso', () => {
        cy.visit('/login');
        cy.title().should('include', 'Iniciar Sesión');
        cy.get('#loginForm').should('be.visible');
        cy.get('#email').should('be.visible');
        cy.get('#password').should('be.visible');
        cy.get('#btnLogin').should('be.visible').and('contain', 'Ingresar');
    });

    it('enlace de recuperación de contraseña (si está habilitado)', () => {
        cy.visit('/login');
        cy.get('body').then(($b) => {
            const forgot = $b.find('a.forgot-link');
            if (forgot.length) {
                cy.wrap(forgot.first()).invoke('attr', 'href').should('include', 'forgot-password');
            }
        });
    });
});
