<?php

declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Controller\CreateFastConferenceController;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProvisionerEntryPointsTest extends WebTestCase
{
    public function test_CreateFastConference_returnsPopupUrlToProvisionerCreate_whenPublicServerShouldProvision(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $router */
        $router = self::getContainer()->get('router');
        $userRepository = self::getContainer()->get(UserRepository::class);
        $serverRepository = self::getContainer()->get(ServerRepository::class);
        $controller = self::getContainer()->get(CreateFastConferenceController::class);

        $currentUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $provisioningServer = $serverRepository->findOneBy([
            'isProvisioningEnabled' => true,
            'isAllowedToCloneForAutoscale' => true,
        ]);

        // Force controller to use a known server so the test is independent from PUBLIC_SERVER env fixture state.
        $injectServer = \Closure::bind(
            static function (CreateFastConferenceController $controller, $server): void {
                $controller->server = $server;
            },
            null,
            CreateFastConferenceController::class,
        );
        $injectServer($controller, $provisioningServer);

        $client->loginUser($currentUser);
        $client->request('GET', $router->generate('app_create_fast_conference'));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string)$client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);
        self::assertArrayHasKey('popups', $payload);
        self::assertNotEmpty($payload['popups']);
        self::assertArrayHasKey('url', $payload['popups'][0]);
        self::assertStringContainsString('/provision', $payload['popups'][0]['url']);
    }

    public function test_StartController_redirectsToProvisionerCreate_whenRoomShouldProvision(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $router */
        $router = self::getContainer()->get('router');
        $userRepository = self::getContainer()->get(UserRepository::class);
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);
        $serverRepository = self::getContainer()->get(ServerRepository::class);

        $user = $userRepository->findOneBy([]);
        $server = $serverRepository->findOneBy([
            'isProvisioningEnabled' => true,
            'isAllowedToCloneForAutoscale' => true,
        ]);
        $room = $roomsRepository->findOneBy(['server' => $server], ['id' => 'ASC']);

        $client->loginUser($user);
        $client->request('GET', $router->generate('room_join', ['t' => 'b', 'room' => $room->getId()]));

        self::assertResponseRedirects($router->generate('app_provisioner_create', ['uidReal' => $room->getUidReal()]));
    }

    public function test_AdHocMeetingController_returnsRedirectUrlToProvisionerCreate_whenServerShouldProvision(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $router */
        $router = self::getContainer()->get('router');
        $userRepository = self::getContainer()->get(UserRepository::class);
        $serverRepository = self::getContainer()->get(ServerRepository::class);

        $currentUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $invitee = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $server = $serverRepository->findOneBy([
            'isProvisioningEnabled' => true,
            'isAllowedToCloneForAutoscale' => true,
        ]);

        $client->loginUser($currentUser);
        $client->request(
            'GET',
            $router->generate('add_hoc_meeting_no_tag', ['userId' => $invitee->getId(), 'serverId' => $server->getId()])
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string)$client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);
        self::assertArrayHasKey('redirectUrl', $payload);
        self::assertStringContainsString('/provision', $payload['redirectUrl']);
    }
}







