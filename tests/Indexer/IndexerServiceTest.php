<?php

namespace App\Tests\Indexer;

use App\Repository\UserRepository;
use App\Service\IndexUser;
use App\Service\IndexUserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IndexerServiceTest extends KernelTestCase
{
    public function testIndexer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $indexer = self::getContainer()->get(IndexUserService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['username' => 'test@local.de']);
        $index = $indexer->indexUser($user);
        self::assertEquals($user->getIndexer(), $index);
        self::assertNull($indexer->indexUser(null));
        $user->setSpezialProperties(null);
        $index = $indexer->indexUser($user);
        self::assertEquals('test@local.de test@local.de test user', $index);
        self::assertNull($indexer->indexUser(null));
    }
    public function testIndexerMobile(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $indexer = self::getContainer()->get(IndexUserService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['username' => 'test@local.de']);
        $index = $indexer->indexUser($user);
        self::assertEquals($user->getIndexer(), $index);
        self::assertNull($indexer->indexUser(null));
        $user->setSpezialProperties(['mobile'=>'+49 (012) 0123456','email1'=>'email1@test.de']);
        $index = $indexer->indexUser($user);
        self::assertEquals('test@local.de test@local.de test user 490120123456 email1@test.de', $index);
        self::assertNull($indexer->indexUser(null));
    }
}
