<?php
declare(strict_types=1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @mixin KernelTestCase
 */
trait EntityCleanupTrait
{
    private array $entitiesToCleanup = [];

    /**
     * use this method as a replacement for $entityManager->persist and the test will clean all entities up
     */
    protected function persistAndTrack(EntityManagerInterface $entityManager, object $entity): void
    {
        $entityManager->persist($entity);
        $this->entitiesToCleanup[] = $entity;
    }

    protected function tearDown(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // Remove in reverse order to handle dependencies
        foreach (array_reverse($this->entitiesToCleanup) as $entity) {
            try {
                $entityManager->remove($entity);
            } catch (\Exception $e) {
                // Entity may already be removed or not managed
            }
        }
        $entityManager->flush();
        $this->entitiesToCleanup = [];

        parent::tearDown();
    }
}
