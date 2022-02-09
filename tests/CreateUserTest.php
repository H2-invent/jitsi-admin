<?php

namespace App\Tests;

use App\Service\UserCreatorService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CreateUserTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $userCreator = self::getContainer()->get(UserCreatorService::class);
        $user = $userCreator->createUser('testme@mail.com', 'testme','firstname', 'lastname');
        self::assertEquals('testme@mail.com', $user->getEmail());
        self::assertEquals('testme', $user->getUsername());
        self::assertEquals('firstname', $user->getFirstName());
        self::assertEquals('lastname', $user->getLastName());
        self::assertEquals('testme testme@mail.com firstname lastname', $user->getIndexer());

        $user = $userCreator->createUser('testmeert@mail.com', 'test@local.de','firstname', 'lastname');
        self::assertEquals('test@local.de', $user->getEmail());
        self::assertEquals('test@local.de', $user->getUsername());
        self::assertEquals('Test', $user->getFirstName());
        self::assertEquals('User', $user->getLastName());
        self::assertEquals('test@local.de test@local.de test user test1 1234', $user->getIndexer());

    }
}
