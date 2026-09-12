<?php

namespace App\Tests\Repository;

use App\Entity\SchedulingTimeUser;
use App\Repository\SchedulingRepository;
use App\Repository\SchedulingTimeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchedulingTimeRepositoryTest extends KernelTestCase
{
    public function testFindSchedulingTimeForUserAndSchedulingReturnsLinkedTimes(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $scheduling = self::getContainer()->get(SchedulingRepository::class)->findOneBy([]);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $time = $scheduling->getSchedulingTimes()->first();

        $link = (new SchedulingTimeUser())
            ->setUser($user)
            ->setScheduleTime($time);
        $entityManager->persist($link);
        $entityManager->flush();

        $repository = self::getContainer()->get(SchedulingTimeRepository::class);
        $result = $repository->findSchedulingTimeForUserAndScheduling($scheduling, $user);

        $this->assertCount(1, $result);
        $this->assertSame($time->getId(), $result[0]->getId());
    }

    public function testFindSchedulingTimeForUserAndSchedulingReturnsEmptyWithoutLink(): void
    {
        self::bootKernel();
        $scheduling = self::getContainer()->get(SchedulingRepository::class)->findOneBy([]);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);

        $repository = self::getContainer()->get(SchedulingTimeRepository::class);

        $this->assertSame([], $repository->findSchedulingTimeForUserAndScheduling($scheduling, $user));
    }
}
