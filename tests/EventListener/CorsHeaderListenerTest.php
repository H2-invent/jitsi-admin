<?php

namespace App\Tests\EventListener;

use App\EventListener\CorsHeaderListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CorsHeaderListenerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    private function dispatch(string $path): Response
    {
        $request = Request::create($path);
        $response = new Response();
        $event = new ResponseEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        (new CorsHeaderListener())($event);

        return $response;
    }

    public function testAddsCorsHeaderForUploadsFile(): void
    {
        $response = $this->dispatch('/uploads/theme/logo.jpg');

        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testAddsCorsHeaderForUploadsPathWithoutDot(): void
    {
        $response = $this->dispatch('/uploads/theme/logo');

        self::assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testDoesNotAddCorsHeaderForOtherPaths(): void
    {
        $response = $this->dispatch('/room/4711');

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testDoesNotAddCorsHeaderForPrefixedUploadsPathWithoutSlash(): void
    {
        $response = $this->dispatch('/uploads-backup/logo.jpg');

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }
}
