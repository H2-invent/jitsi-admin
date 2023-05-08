<?php

namespace App\Tests\Indexer;

use App\Entity\AddressGroup;
use App\Service\IndexGroupsService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IndexGroupsServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $indexer = self::getContainer()->get(IndexGroupsService::class);
        $group = new AddressGroup();
        $group->setName('TestMe With Space__');
        $index = $indexer->indexGroup($group);
        self::assertEquals('testme with space__ ', $index);
        self::assertNull($indexer->indexGroup(null));
    }
}
