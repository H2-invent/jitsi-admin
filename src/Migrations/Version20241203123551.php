<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241203123551 extends AbstractMigration
{
    private const TABLE_NAME = 'uploaded_recording';
    private const COLUMN_NAME_FILENAME = 'filename';
    private const COLUMN_NAME_ROOM_ID = 'room_id';
    private const COLUMN_NAME_CREATED_AT = 'created_at';
    private const COLUMN_NAME_TYPE = 'type';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn(self::COLUMN_NAME_FILENAME, Types::STRING, ['length' => 500])->setNotnull(true);
        $table->addColumn(self::COLUMN_NAME_CREATED_AT, Types::DATETIME_IMMUTABLE)->setNotnull(true);
        $table->addColumn(self::COLUMN_NAME_ROOM_ID, Types::INTEGER)->setNotnull(true);
        $table->addColumn(self::COLUMN_NAME_TYPE, Types::STRING, ['length' => 255])->setNotnull(true);
        $table->addIndex(['room_id'], 'IDX_3CC9EEAD54177093');
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('rooms', [self::COLUMN_NAME_ROOM_ID], ['id'], [], 'FK_3CC9EEAD54177093');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->removeForeignKey('FK_3CC9EEAD54177093');
        $schema->dropTable(self::TABLE_NAME);
    }
}
