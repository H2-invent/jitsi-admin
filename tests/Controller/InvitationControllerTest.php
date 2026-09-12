<?php

namespace App\Tests\Controller;

use App\Controller\InvitationController;
use App\Entity\User;
use App\Service\InviteService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class InvitationControllerTest extends WebTestCase
{
    public function testIndexConnectsInvitedUserAndRedirects(): void
    {
        static::createClient();
        /** @var InvitationController $controller */
        $controller = self::getContainer()->get(InvitationController::class);

        $currentUser = new User();
        $currentUser->setEmail('test@local.de');
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken($currentUser, 'main', [])
        );

        $inviteService = $this->createMock(InviteService::class);
        $inviteService->expects($this->once())->method('connectUserWithEmail');

        $invited = new User();
        $invited->setEmail('invited@local.de');

        $response = $controller->index($invited, $inviteService, new Request());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/room/dashboard', $response->headers->get('Location'));
    }
}
