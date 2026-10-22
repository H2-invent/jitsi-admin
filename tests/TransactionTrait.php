<?php
declare(strict_types=1);

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @mixin KernelTestCase
 */
trait TransactionTrait
{
    private ?EntityManagerInterface $entityManager = null;
    private ?Connection $connection = null;

    /**
     * initialize transaction after the kernel is booted (via createClient or bootKernel)
     * call this after createClient() in WebTestCase tests
     */
    protected function beginTransaction(): void
    {
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null && $this->connection->isTransactionActive())  {
            $this->connection->rollBack();
        }

        $this->entityManager?->clear();
        $this->entityManager = null;
        $this->connection = null;

        parent::tearDown();
    }
}
