<?php

namespace App\Tests\Controller;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalloutAPIControllerTest extends WebTestCase
{
    private const VALID_HEADERS = ['HTTP_AUTHORIZATION' => 'Bearer 123456'];

    private function createCalloutSession(int $state): CalloutSession
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'ldapUser@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'This is a room with Lobby']);

        $callerId = new CallerId();
        $callerId->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setUser($user)
            ->setCallerId('987654321');
        $em->persist($callerId);

        $session = new CalloutSession();
        $session->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($room->getModerator())
            ->setState($state)
            ->setUid('testsession' . uniqid())
            ->setLeftRetries(2);
        $em->persist($session);
        $em->flush();

        return $session;
    }

    public function testPoolsReturnEmptyCallList(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);

        $client->request('GET', '/api/v1/call/out/');
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"calls":[]}', $client->getResponse()->getContent());

        $client->request('GET', '/api/v1/call/out/dial/');
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"calls":[]}', $client->getResponse()->getContent());

        $client->request('GET', '/api/v1/call/out/on_hold/');
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"calls":[]}', $client->getResponse()->getContent());
    }

    public function testActionsRejectInvalidAuthorization(): void
    {
        $client = static::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer invalid']);

        $client->request('GET', '/api/v1/call/out/');
        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonStringEqualsJsonString('{"authorized":false}', $client->getResponse()->getContent());

        $client->request('GET', '/api/v1/call/out/dial/');
        $this->assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/v1/call/out/dial/unknown-id');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testSessionActionsReturnNotFoundForUnknownId(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);

        $actions = ['dial', 'ringing', 'refuse', 'error', 'unreachable', 'timeout', 'later', 'occupied', 'back'];
        foreach ($actions as $action) {
            $client->request('GET', '/api/v1/call/out/' . $action . '/unknown-id');
            $this->assertResponseIsSuccessful();
            $this->assertJsonStringEqualsJsonString(
                '{"error":true,"reason":"NO_SESSION_ID_FOUND"}',
                $client->getResponse()->getContent(),
                'Action ' . $action . ' should report a missing session'
            );
        }
    }

    public function testDialRingingLaterAndBack(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);
        $session = $this->createCalloutSession(CalloutSession::$INITIATED);

        $client->request('GET', '/api/v1/call/out/dial/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('OK', $data['status']);
        $this->assertArrayHasKey('dial', $data['links']);

        $client->request('GET', '/api/v1/call/out/ringing/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('RINGING', $data['status']);
        $this->assertSame('987654321', $data['pin']);
        $this->assertSame('12341232', $data['room_number']);

        $client->request('GET', '/api/v1/call/out/later/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('ON_HOLD', $data['status']);
        $this->assertSame('987654321', $data['pin']);

        $client->request('GET', '/api/v1/call/out/back/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('DIALED', $data['status']);
        $this->assertArrayHasKey('dial', $data['links']);
    }

    public function testOccupiedSetsSessionOnHold(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);
        $session = $this->createCalloutSession(CalloutSession::$INITIATED);

        $client->request('GET', '/api/v1/call/out/dial/' . $session->getUid());
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/call/out/occupied/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('ON_HOLD', $data['status']);
        $this->assertSame('987654321', $data['pin']);
    }

    public function testTimeoutSetsSessionOnHold(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);
        $session = $this->createCalloutSession(CalloutSession::$INITIATED);

        $client->request('GET', '/api/v1/call/out/dial/' . $session->getUid());
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/call/out/timeout/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('ON_HOLD', $data['status']);
    }

    public function testRefuseDeletesSession(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);
        $session = $this->createCalloutSession(CalloutSession::$INITIATED);

        $client->request('GET', '/api/v1/call/out/dial/' . $session->getUid());
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/call/out/refuse/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            '{"status":"DELETED","links":[]}',
            $client->getResponse()->getContent()
        );
        $this->assertNull(
            self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['uid' => $session->getUid()])
        );
    }

    public function testErrorDeletesSession(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);
        $session = $this->createCalloutSession(CalloutSession::$INITIATED);

        $client->request('GET', '/api/v1/call/out/dial/' . $session->getUid());
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/call/out/error/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            '{"status":"DELETED","links":[]}',
            $client->getResponse()->getContent()
        );
        $this->assertNull(
            self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['uid' => $session->getUid()])
        );
    }

    public function testUnreachableDeletesSession(): void
    {
        $client = static::createClient([], self::VALID_HEADERS);
        $session = $this->createCalloutSession(CalloutSession::$INITIATED);

        $client->request('GET', '/api/v1/call/out/dial/' . $session->getUid());
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/call/out/unreachable/' . $session->getUid());
        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            '{"status":"DELETED","links":[]}',
            $client->getResponse()->getContent()
        );
        $this->assertNull(
            self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['uid' => $session->getUid()])
        );
    }
}
