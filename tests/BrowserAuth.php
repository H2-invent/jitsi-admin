<?php

declare(strict_types=1);

namespace App\Tests;

use Pest\Browser\Api\PendingAwaitablePage;

trait BrowserAuth
{
    private static string $keycloakUser = 'jitsi@admin.de';
    private static string $keycloakPassword = 'jitsiadmin';

    private function login(PendingAwaitablePage $page): PendingAwaitablePage
    {
        $page->click('.hp-nav-right .btn-primary');
        $page->type('#username', self::$keycloakUser);
        $page->type('#password', self::$keycloakPassword);
        $page->click('#kc-login');

        return $page;
    }
}
