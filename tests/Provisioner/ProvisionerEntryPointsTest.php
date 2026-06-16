<?php

declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Controller\CreateFastConferenceController;
use App\Tests\Builder\RoomsBuilder;
use App\Tests\Builder\ServerBuilder;
use App\Tests\Builder\UserBuilder;
use App\Tests\TransactionTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProvisionerEntryPointsTest extends WebTestCase
{
    use TransactionTrait;

    public function test_CreateFastConference_returnsPopupUrlToProvisionerCreate_whenPublicServerShouldProvision(): void
    {
        $client = static::createClient();
        $this->beginTransaction();
        /** @var UrlGeneratorInterface $router */
        $router = self::getContainer()->get('router');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $controller = self::getContainer()->get(CreateFastConferenceController::class);

        $currentUser = UserBuilder::create()->persist($entityManager);
        $provisioningServer = ServerBuilder::create()
            ->withAdministrator($currentUser)
            ->withUser($currentUser)
            ->withProvisioning(true, true)
            ->persist($entityManager);

        // force controller to use a known server so the test is independent from PUBLIC_SERVER env fixture state
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
        $this->beginTransaction();
        /** @var UrlGeneratorInterface $router */
        $router = self::getContainer()->get('router');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = UserBuilder::create()->persist($entityManager);
        $server = ServerBuilder::create()
            ->withAdministrator($user)
            ->withUser($user)
            ->withProvisioning(true, true)
            ->persist($entityManager);
        $room = RoomsBuilder::create($server)
            ->withModerator($user)
            ->withCreator($user)
            ->withParticipant($user)
            ->persist($entityManager);

        $client->loginUser($user);
        $client->request('GET', $router->generate('room_join', ['t' => 'b', 'room' => $room->getId()]));

        self::assertResponseRedirects($router->generate('app_provisioner_create', ['uidReal' => $room->getUidReal()]));
    }

    public function test_AdHocMeetingController_returnsRedirectUrlToProvisionerCreate_whenServerShouldProvision(): void
    {
        $client = static::createClient();
        $this->beginTransaction();
        /** @var UrlGeneratorInterface $router */
        $router = self::getContainer()->get('router');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = UserBuilder::create()->persist($entityManager);
        $server = ServerBuilder::create()
            ->withAdministrator($user)
            ->withUser($user)
            ->withProvisioning(true, true)
            ->persist($entityManager);

        $user->addServer($server);
        $user->addAddressbook($user);
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request(
            'GET',
            $router->generate('add_hoc_meeting_no_tag', ['userId' => $user->getId(), 'serverId' => $server->getId()])
        );

        self::assertResponseIsSuccessful();
        $payload = json_decode((string)$client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);
        self::assertArrayHasKey('redirectUrl', $payload);
        self::assertStringContainsString('/provision', $payload['redirectUrl']);
    }
}







