<?php

namespace App\Tests\Repository;

use App\Entity\Scheduling;
use App\Repository\SchedulingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchedulingRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheSchedulingEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(SchedulingRepository::class);

        $this->assertInstanceOf(SchedulingRepository::class, $repository);
        $this->assertSame(Scheduling::class, $repository->getClassName());
    }
}
