describe('Dashoard', function() {
    before(browser => browser.navigateTo('http://localhost:8000'));

    it('Demo test ecosia.org', function(browser) {
        browser
            .waitForElementVisible('body')
    });

    after(browser => browser.end());
});
