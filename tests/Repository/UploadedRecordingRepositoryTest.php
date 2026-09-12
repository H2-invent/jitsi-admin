<?php

namespace App\Tests\Repository;

use App\Entity\UploadedRecording;
use App\Repository\UploadedRecordingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UploadedRecordingRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheUploadedRecordingEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(UploadedRecordingRepository::class);

        $this->assertInstanceOf(UploadedRecordingRepository::class, $repository);
        $this->assertSame(UploadedRecording::class, $repository->getClassName());
    }
}
