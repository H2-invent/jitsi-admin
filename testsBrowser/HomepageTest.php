<?php

declare(strict_types=1);

test('homepage loads successfully', function () {
    $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ])
        ->assertSee('Jitsi Admin')
        ->assertPresent('.hp-nav-right')
        ->assertPresent('.hp-hero-cta');
});

test('navigation bar shows login and register buttons', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->assertSeeIn('.hp-nav-right', 'Login');
    $page->assertSeeIn('.hp-nav-right', 'Register');
});

test('hero section shows login and register buttons', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->assertPresent('.hp-hero-cta .btn-primary');
    $page->assertPresent('.hp-hero-cta .btn-outline-primary');
});

test('language switcher is visible on homepage', function () {
    $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ])
        ->assertPresent('#langToggle')
        ->assertVisible('#langToggle');
});

test('clicking language toggle opens language menu', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('#langToggle');
    $page->assertVisible('#langMenu');
    $page->assertSeeIn('#langMenu', 'English');
    $page->assertSeeIn('#langMenu', 'Deutsch');
});

test('language switch to German navigates to /de/', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('#langToggle');
    $page->assertVisible('#langMenu');
    $page->click('#langMenu a[href*="/de/"]');
    $page->assertPathContains('/de/');
    $page->assertSee('Jitsi Admin');
});

test('language switch to English navigates to /', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/de/', [
        'ignoreHTTPSErrors' => true,
    ]);

    $page->click('#langToggle');
    $page->assertVisible('#langMenu');
    $page->click('#langMenu a[href="/"]');
    $page->assertPathIs('/');
    $page->assertSee('Jitsi Admin');
});
