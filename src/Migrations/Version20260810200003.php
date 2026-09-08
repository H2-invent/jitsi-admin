<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810200003 extends AbstractMigration
{
    private const TABLE_NAME = 'rooms';
    private const INDEX_NAME = 'idx_rooms_schedule_meeting';

    public function getDescription(): string
    {
        return 'Create index on rooms.schedule_meeting for query optimization';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addIndex(['schedule_meeting'], self::INDEX_NAME);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropIndex(self::INDEX_NAME);
    }
}
