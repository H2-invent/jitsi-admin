<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708160947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->addColumn('is_fast_conference', 'boolean')
            ->setNotnull(false)
            ->setDefault(null)
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->dropColumn('is_fast_conference')
        ;
    }
}
