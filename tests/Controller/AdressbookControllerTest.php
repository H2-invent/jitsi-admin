<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdressbookControllerTest extends WebTestCase
{
    public function testIndexRemovesUserFromAddressbookAndRedirects(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $contact = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $contactId = $contact->getId();
        $client->loginUser($user);

        $client->request('GET', '/room/adressbook/remove?id=' . $contactId);

        $this->assertResponseRedirects('/room/dashboard');
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertFalse($updated->getAddressbook()->contains($contact));
    }

    public function testRemoveAjaxRemovesUser(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $contact = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/remove-ajax?id=' . $contact->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"ok":true}', $client->getResponse()->getContent());
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertFalse($updated->getAddressbook()->contains($contact));
    }

    public function testRemoveAjaxUnknownUserReturnsNotFound(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/remove-ajax?id=99999999');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testAddAjaxRejectsMissingEmail(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/add-ajax');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testAddAjaxRejectsInvalidEmail(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/add-ajax', ['email' => 'not-an-email']);

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testAddAjaxRejectsAddingSelf(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/add-ajax', ['email' => 'test@local.de']);

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testAddAjaxAddsExistingUser(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $contact = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/add-ajax', ['email' => 'test@local4.de']);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"ok":true}', $client->getResponse()->getContent());
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertTrue($updated->getAddressbook()->contains($contact));
    }

    public function testAddAjaxRejectsAlreadyKnownContact(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/add-ajax', ['email' => 'test@local2.de']);

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testAddAjaxCreatesUnknownUserWhenAllowed(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/adressbook/add-ajax', ['email' => 'brandnewcontact@example.com']);

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"ok":true}', $client->getResponse()->getContent());
        $created = $userRepo->findOneBy(['email' => 'brandnewcontact@example.com']);
        $this->assertNotNull($created);
        $updated = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertTrue($updated->getAddressbook()->contains($created));
    }

    public function testNewContactModalRenders(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/adressbook/new-contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }
}
