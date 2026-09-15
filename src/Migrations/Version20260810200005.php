<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810200005 extends AbstractMigration
{
    private const TABLE_NAME = 'rooms';
    private const INDEX_NAME = 'idx_rooms_time_filter_composite';

    public function getDescription(): string
    {
        return 'Create composite index on rooms for the most common dashboard time-filter queries';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addIndex(
            ['end_date_utc', 'start_utc', 'schedule_meeting', 'persistant_room'],
            self::INDEX_NAME
        );
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->dropIndex(self::INDEX_NAME);
    }
}
