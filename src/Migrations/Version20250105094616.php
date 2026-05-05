<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250105094616 extends AbstractMigration
{
    private const TABLE_NAME = 'recording';
    private const COLUMN_NAME_ROOM_ID = 'room_id';
    private const COLUMN_NAME_USER_ID = 'user_id';
    private const COLUMN_RECORDING_ID = 'recording_id';
    private const COLUMN_NAME_CREATED_AT = 'created_at';
    private const COLUMN_UID = 'uid';
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn(self::COLUMN_NAME_CREATED_AT, Types::DATETIME_IMMUTABLE)->setNotnull(true);
        $table->addColumn(self::COLUMN_NAME_ROOM_ID, Types::INTEGER)->setNotnull(true);
        $table->addColumn(self::COLUMN_NAME_USER_ID, Types::INTEGER)->setNotnull(false);
        $table->addColumn(self::COLUMN_UID, Types::STRING, ['length' => 255])->setNotnull(true);
        $table->addColumn(self::COLUMN_RECORDING_ID, Types::STRING, ['length' => 255])->setNotnull(false);
        $table->addIndex(['room_id'], 'IDX_BB532B5354177093');
        $table->addIndex(['user_id'], 'IDX_BB532B53A76ED395');
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('rooms', [self::COLUMN_NAME_ROOM_ID], ['id'], [], 'FK_BB532B5354177093');
        $table->addForeignKeyConstraint('fos_user', [self::COLUMN_NAME_USER_ID], ['id'], [], 'FK_BB532B53A76ED395');

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->removeForeignKey('FK_BB532B5354177093');
        $table->removeForeignKey('FK_BB532B53A76ED395');
        $schema->dropTable(self::TABLE_NAME);

    }
}
