<?php

namespace App\Tests\Controller;

use App\Controller\CatchAllController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatchAllControllerTest extends WebTestCase
{
    public function testRedirectToDefaultUsesFirstPathSegment(): void
    {
        static::createClient();
        /** @var CatchAllController $controller */
        $controller = self::getContainer()->get(CatchAllController::class);

        $response = $controller->redirectToDefault('my-conference/extra/path');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/m/my-conference', $response->headers->get('Location'));
    }
}
