<?php

namespace App\Tests\Repository;

use App\Entity\Subscriber;
use App\Repository\SubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SubscriberRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheSubscriberEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(SubscriberRepository::class);

        $this->assertInstanceOf(SubscriberRepository::class, $repository);
        $this->assertSame(Subscriber::class, $repository->getClassName());
    }
}
