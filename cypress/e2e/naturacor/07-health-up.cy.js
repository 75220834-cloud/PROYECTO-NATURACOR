/**
 * Comprobación HTTP del endpoint de salud (/up).
 * No abre navegador completo; usa cy.request (rápido en CI).
 */
describe('NATURACOR — salud del servicio', () => {
    it('GET /up responde 200', () => {
        cy.request({ url: '/up', failOnStatusCode: false }).then((res) => {
            expect(res.status).to.eq(200);
            expect(String(res.body).length).to.be.greaterThan(0);
        });
    });
});
