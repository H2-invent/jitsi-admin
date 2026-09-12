<?php

namespace App\Tests\Repository;

use App\Entity\Waitinglist;
use App\Repository\WaitinglistRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WaitinglistRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheWaitinglistEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(WaitinglistRepository::class);

        $this->assertInstanceOf(WaitinglistRepository::class, $repository);
        $this->assertSame(Waitinglist::class, $repository->getClassName());
    }
}
