<?php

namespace App\Tests\Repository;

use App\Entity\Recording;
use App\Repository\RecordingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RecordingRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheRecordingEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(RecordingRepository::class);

        $this->assertInstanceOf(RecordingRepository::class, $repository);
        $this->assertSame(Recording::class, $repository->getClassName());
    }
}
