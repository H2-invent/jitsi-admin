<?php

namespace App\Tests\Controller;

use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdHocMeetingControllerTest extends WebTestCase
{
    public function testConfirmationRenders(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $contact = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/adhoc/confirmation/' . $contact->getId() . '/' . $server->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testIndexCreatesAdHocMeeting(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $contact = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/adhoc/meeting/' . $contact->getId() . '/' . $server->getId());

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertArrayHasKey('popups', $data);
        $this->assertNotEmpty($data['popups']);
    }

    public function testIndexRejectsUserOutsideAddressbook(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $contact = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/adhoc/meeting/' . $contact->getId() . '/' . $server->getId());

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertArrayNotHasKey('popups', $data);
    }
}
