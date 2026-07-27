<?php

declare(strict_types=1);

test('clicking login from homepage redirects to Keycloak login', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('.hp-nav-right .btn-primary');
    $page->assertHostIs('jitsi-admin.ddev.site');
    $page->assertSee('Sign in');
});

test('clicking register from homepage redirects to Keycloak registration', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('.hp-nav-right .btn-outline-primary');
    $page->assertPathContains('registrations');
});

test('Keycloak login page contains username and password fields', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('.hp-nav-right .btn-primary');
    $page->assertPresent('#username');
    $page->assertPresent('#password');
    $page->assertPresent('#kc-login');
});

test('Keycloak login authenticates and redirects to dashboard', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('.hp-nav-right .btn-primary');

    $page->type('#username', 'jitsi@admin.de');
    $page->type('#password', 'jitsiadmin');
    $page->click('#kc-login');

    $page->assertUrlIs('https://jitsi-admin.ddev.site/room/dashboard');
});
