<?php

declare(strict_types=1);

uses(App\Tests\BrowserAuth::class);

test('dashboard is accessible and renders its main interface after Keycloak login', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);
    $page = $this->login($page);

    $page->assertUrlIs('https://jitsi-admin.ddev.site/room/dashboard');
    $page->assertPresent('#mainContent');
    $page->assertPresent('.navigation');
    $page->assertPresent('.subnavigation');
    $page->assertPresent('.profile');
    $page->assertPresent('a.logout');
    $page->assertPresent('.footer');
    $page->assertPresent('#ex1');
    $page->assertPresent('#ex1-tab-1-tab');
    $page->assertPresent('#ex1-tab-2-tab');
    $page->assertPresent('#ex1-tab-3-tab');
    $page->assertPresent('#createNewConference');
    $page->assertPresent('#sidebar');
    $page->assertPresent('#favorite-Container');
});
