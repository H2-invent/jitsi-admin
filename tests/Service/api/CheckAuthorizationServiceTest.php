<?php

namespace App\Tests\Service\api;

use App\Service\api\CheckAuthorizationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckAuthorizationServiceTest extends TestCase
{
    public function testReturnsNullWhenAuthorizationHeaderMatchesToken(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid-token');

        self::assertNull(CheckAuthorizationService::checkHEader($request, 'Bearer valid-token'));
    }

    public function testReturnsUnauthorizedJsonWhenHeaderDoesNotMatch(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer wrong-token');

        $response = CheckAuthorizationService::checkHEader($request, 'Bearer valid-token');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['authorized' => false], json_decode($response->getContent(), true));
    }

    public function testReturnsUnauthorizedJsonWhenAuthorizationHeaderIsMissing(): void
    {
        $request = new Request();

        $response = CheckAuthorizationService::checkHEader($request, 'Bearer valid-token');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['authorized' => false], json_decode($response->getContent(), true));
    }

    public function testReturnsNullWhenBothHeaderAndTokenAreEmpty(): void
    {
        $request = new Request();

        self::assertNull(CheckAuthorizationService::checkHEader($request, null));
    }
}
