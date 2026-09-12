<?php

namespace App\Tests\Controller;

use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServersControllerTest extends WebTestCase
{
    public function testServerAddRendersNewServerForm(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/server/add');

        $this->assertResponseIsSuccessful();
    }

    public function testServerAddCreatesNewServer(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/server/add');
        $form = $crawler->filter('form')->form();
        $values = $form->getPhpValues();
        $name = array_key_first($values);
        $values[$name]['url'] = 'newserver.example.com';
        $values[$name]['serverName'] = 'New Server';
        $values[$name]['jwtModeratorPosition'] = '0';
        $client->request('POST', '/server/add', $values);

        $this->assertResponseRedirects('/room/dashboard');
        $this->assertNotNull(self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'newserver.example.com']));
    }

    public function testServerAddRejectsForeignKeyOnEdit(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/add?id=' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testServerEnterpriseRendersForAdministrator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/enterprise?id=' . $server->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testServerEnterpriseRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/enterprise?id=' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testRoomAddUserRendersPermissionModal(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/add-user?id=' . $server->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testRoomAddUserAddsPermission(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/server/add-user?id=' . $server->getId());
        $form = $crawler->filter('form')->form();
        $values = $form->getPhpValues();
        $name = array_key_first($values);
        $values[$name]['member'] = 'serverpermission@example.com';
        $client->request('POST', '/server/add-user?id=' . $server->getId(), $values);

        $this->assertResponseRedirects('/room/dashboard');
        $newUser = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'serverpermission@example.com']);
        $this->assertNotNull($newUser);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertTrue($server->getUser()->contains($newUser));
    }

    public function testRoomAddUserRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/add-user?id=' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testServerUserRemoveRemovesUser(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $admin = $userRepo->findOneBy(['email' => 'test@local.de']);
        $member = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $server->addUser($member);
        $em->persist($server);
        $em->flush();
        $client->loginUser($admin);

        $client->request('GET', '/server/user/remove?id=' . $server->getId() . '&user=' . $member->getId());

        $this->assertResponseRedirects('/room/dashboard');
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertFalse($server->getUser()->contains($member));
    }

    public function testServerUserRemoveWithoutPermissionKeepsUser(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $outsider = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $member = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $server->addUser($member);
        $em->persist($server);
        $em->flush();
        $client->loginUser($outsider);

        $client->request('GET', '/server/user/remove?id=' . $server->getId() . '&user=' . $member->getId());

        $this->assertResponseRedirects('/room/dashboard');
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertTrue($server->getUser()->contains($member));
    }

    public function testServerDeleteRemovesServerUsers(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/delete?id=' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertCount(0, $server->getUser());
    }

    public function testServerDeleteRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/delete?id=' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $this->assertNotCount(0, $server->getUser());
    }

    public function testServercheckEmailWithoutSmtpHostRedirects(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/server/check/email?id=' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testServercheckEmailWithoutServerRedirects(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/server/check/email?id=999999');

        $this->assertResponseRedirects('/room/dashboard');
    }
}
