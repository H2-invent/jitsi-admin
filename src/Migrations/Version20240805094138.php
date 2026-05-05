<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240805094138 extends AbstractMigration
{
    private const TABLE_NAME = 'server';
    private const COLUMN_NAME = 'livekit_middleware_url';
    public function getDescription(): string
    {
        return 'Adds the livekit base url  ';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME,Types::STRING)
            ->setLength(512)
            ->setDefault(null)
            ->setNotnull(false);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropColumn(self::COLUMN_NAME);

    }

}
