<?php

declare(strict_types=1);

use Pest\Browser\Browsable;
use Pest\Browser\Plugin;
use Pest\Browser\Playwright\Client;
use Pest\Browser\Playwright\Playwright;
use Pest\Browser\ServerManager;
use Pest\Browser\Support\Screenshot;

uses(App\Tests\TestCase::class)->in('..');

uses(Browsable::class)->in('../testsBrowser');

// Workarounds for https://github.com/pestphp/pest/issues/1486
uses()->beforeAll(function (): void {
    if (! Plugin::$booted) {
        Plugin::$booted = true;
        if ((getenv('PLAYWRIGHT_HEADED') ?: $_ENV['PLAYWRIGHT_HEADED'] ?? '') === 'true') {
            Playwright::headed();
        }
        ServerManager::instance()->playwright()->start();
        Screenshot::cleanup();
    }
})->in('../testsBrowser');

uses()->beforeEach(function (): void {
    Client::instance()->connectTo(
        ServerManager::instance()->playwright()->url(),
    );
    ServerManager::instance()->http()->bootstrap();
})->in('../testsBrowser');

uses()->afterEach(function (): void {
    ServerManager::instance()->http()->flush();
    Playwright::reset();
})->in('../testsBrowser');
