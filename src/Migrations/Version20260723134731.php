<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723134731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->addColumn('created_at', Types::DATETIME_IMMUTABLE)
            ->setNotnull(false)
        ;
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('rooms')
            ->dropColumn('created_at')
        ;
    }
}
