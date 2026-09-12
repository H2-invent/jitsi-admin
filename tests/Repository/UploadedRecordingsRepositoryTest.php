<?php

namespace App\Tests\Repository;

use App\Repository\UploadedRecordingsRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\TestCase;

class UploadedRecordingsRepositoryTest extends TestCase
{
    public function testIsAServiceEntityRepository(): void
    {
        $this->assertTrue(class_exists(UploadedRecordingsRepository::class));
        $this->assertTrue(is_subclass_of(UploadedRecordingsRepository::class, ServiceEntityRepository::class));
    }
}
