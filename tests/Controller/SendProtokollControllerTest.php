<?php

namespace App\Tests\Controller;

use App\Controller\SendProtokollController;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Summary\SendSummaryViaEmailService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SendProtokollControllerTest extends WebTestCase
{
    public function testIndexSendsSummaryForModerator(): void
    {
        static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', [])
        );

        $summaryService = $this->createMock(SendSummaryViaEmailService::class);
        $summaryService->expects($this->once())->method('sendSummaryForRoom')->with($room);
        self::getContainer()->set(SendSummaryViaEmailService::class, $summaryService);

        /** @var SendProtokollController $controller */
        $controller = self::getContainer()->get(SendProtokollController::class);

        $response = $controller->index($room);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/room/dashboard', $response->headers->get('Location'));
    }

    public function testIndexRedirectsForeignKeyWithoutSending(): void
    {
        static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);

        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, 'main', [])
        );

        $summaryService = $this->createMock(SendSummaryViaEmailService::class);
        $summaryService->expects($this->never())->method('sendSummaryForRoom');
        self::getContainer()->set(SendSummaryViaEmailService::class, $summaryService);

        /** @var SendProtokollController $controller */
        $controller = self::getContainer()->get(SendProtokollController::class);

        $response = $controller->index($room);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/room/dashboard', $response->headers->get('Location'));
    }
}
