<?php

namespace App\Tests\Deputy;

use App\Entity\Deputy;
use App\Repository\DeputyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeputyRepositoryTest extends KernelTestCase
{
    public function testFindForManagerReturnsEmptyArrayForManagerWithoutDeputies(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $deputyRepo = $this->getContainer()->get(DeputyRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);

        $manager = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->assertNotNull($manager);

        $this->assertSame([], $deputyRepo->findForManager($manager));
    }

    public function testFindForManagerReturnsDeputiesIndexedByDeputyId(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $deputyRepo = $this->getContainer()->get(DeputyRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $em = $this->getContainer()->get(EntityManagerInterface::class);

        $manager = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputyUser1 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $deputyUser2 = $userRepo->findOneBy(['email' => 'test@local3.de']);

        $this->assertNotNull($manager);
        $this->assertNotNull($deputyUser1);
        $this->assertNotNull($deputyUser2);

        $deputy1 = new Deputy();
        $deputy1->setManager($manager)
            ->setDeputy($deputyUser1)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsFromLdap(false);

        $deputy2 = new Deputy();
        $deputy2->setManager($manager)
            ->setDeputy($deputyUser2)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsFromLdap(true);

        $em->persist($deputy1);
        $em->persist($deputy2);
        $em->flush();

        $result = $deputyRepo->findForManager($manager);

        $deputyUser1Id = $deputyUser1->getId();
        $deputyUser2Id = $deputyUser2->getId();
        $this->assertNotNull($deputyUser1Id);
        $this->assertNotNull($deputyUser2Id);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($deputyUser1Id, $result);
        $this->assertArrayHasKey($deputyUser2Id, $result);
        $this->assertSame($deputy1->getId(), $result[$deputyUser1Id]->getId());
        $this->assertSame($deputy2->getId(), $result[$deputyUser2Id]->getId());
        $this->assertFalse($result[$deputyUser1Id]->isIsFromLdap());
        $this->assertTrue($result[$deputyUser2Id]->isIsFromLdap());
    }
}
