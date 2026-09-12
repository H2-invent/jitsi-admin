<?php

namespace App\Tests\Controller;

use App\Controller\TestNotificationController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestNotificationControllerTest extends WebTestCase
{
    public function testIndexReturnsStreamedResponse(): void
    {
        static::createClient();
        /** @var TestNotificationController $controller */
        $controller = self::getContainer()->get(TestNotificationController::class);

        $response = $controller->index();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/room/test/notification');

        $this->assertResponseRedirects();
    }
}
