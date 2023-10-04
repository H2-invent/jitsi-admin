<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230929162618 extends AbstractMigration
{
    private const TABLE_NAME = 'room_status';
    private const COLUMN_NAME = 'room_id';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->getColumn(self::COLUMN_NAME)
            ->setDefault(null)
            ->setNotnull(false);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->getColumn(self::COLUMN_NAME)
            ->setNotnull(true);

    }
}
