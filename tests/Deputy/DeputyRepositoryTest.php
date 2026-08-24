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
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(false);

        $deputy2 = new Deputy();
        $deputy2->setManager($manager)
            ->setDeputy($deputyUser2)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(true);

        $em->persist($deputy1);
        $em->persist($deputy2);
        $em->flush();

        $result = $deputyRepo->findForManager($manager);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey($deputyUser1->getId(), $result);
        $this->assertArrayHasKey($deputyUser2->getId(), $result);
        $this->assertSame($deputy1->getId(), $result[$deputyUser1->getId()]->getId());
        $this->assertSame($deputy2->getId(), $result[$deputyUser2->getId()]->getId());
        $this->assertFalse($result[$deputyUser1->getId()]->isIsFromLdap());
        $this->assertTrue($result[$deputyUser2->getId()]->isIsFromLdap());
    }
}
