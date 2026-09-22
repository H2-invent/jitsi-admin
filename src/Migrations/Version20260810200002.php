<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810200002 extends AbstractMigration
{
    private const TABLE_NAME = 'rooms';
    private const INDEX_NAME = 'idx_rooms_start_utc';

    public function getDescription(): string
    {
        return 'Create index on rooms.start_utc for time-based query optimization';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addIndex(['start_utc'], self::INDEX_NAME);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropIndex(self::INDEX_NAME);
    }
}
