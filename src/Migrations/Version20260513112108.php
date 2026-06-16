<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513112108 extends AbstractMigration
{
    private const TABLE_NAME = 'transcription';
    private const FOREIGN_KEY_NAME = 'FK_329CE98454177093';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', Types::INTEGER)
            ->setAutoincrement(true)
            ->setNotnull(true)
        ;
        $table->addColumn('room_id', Types::INTEGER)
            ->setNotnull(true)
        ;
        $table->addColumn('text', Types::TEXT)
            ->setNotnull(true)
        ;
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE)
            ->setNotnull(true)
        ;
        $table->addIndex(['room_id']);
        $table->addForeignKeyConstraint('rooms', ['room_id'], ['id'], name: self::FOREIGN_KEY_NAME);
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->removeForeignKey(self::FOREIGN_KEY_NAME);
        $schema->dropTable(self::TABLE_NAME);
    }
}
