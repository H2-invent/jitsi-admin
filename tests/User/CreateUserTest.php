<?php

namespace App\Tests\User;

use App\Repository\UserRepository;
use App\Service\UserCreatorService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CreateUserTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $userCreator = self::getContainer()->get(UserCreatorService::class);
        $user = $userCreator->createUser('testme@mail.com', 'testme', 'firstname', 'lastname');
        self::assertEquals('testme@mail.com', $user->getEmail());
        self::assertEquals('testme', $user->getUsername());
        self::assertEquals('firstname', $user->getFirstName());
        self::assertEquals('lastname', $user->getLastName());
        self::assertEquals('testme testme@mail.com firstname lastname', $user->getIndexer());

        $user = $userCreator->createUser('testmeert@mail.com', 'test@local.de', 'firstname', 'lastname');
        self::assertEquals('test@local.de', $user->getEmail());
        self::assertEquals('test@local.de', $user->getUsername());
        self::assertEquals('Test', $user->getFirstName());
        self::assertEquals('User', $user->getLastName());
        self::assertEquals('test@local.de test@local.de test user test1 1234 0123456789', $user->getIndexer());


        $userRepo = self::getContainer()->get(UserRepository::class);
        $userFind = $userRepo->findOneBy(['email' => 'testme@mail.com']);
        self::assertNotNull($userFind);

        $user = $userCreator->createUser('testmedryRun@mail.com', 'testme', 'firstname', 'lastname', true);
        self::assertNotNull($user);
        $userFind = $userRepo->findOneBy(['email' => 'testmedryRun@mail.com']);
        self::assertNull($userFind);
    }
}
