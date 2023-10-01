<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230927072637 extends AbstractMigration
{
    private const TABLE_NAME = 'server_tag';
    private const COLUMN_NAME_SERVER = 'server_id';
    private const COLUMN_NAME_TAG = 'tag_id';

    private const FOREIGN_COLUMN_NAME_SERVER = 'id';
    private const FOREIGN_TABLE_NAME_SERVER = 'server';

    private const FOREIGN_COLUMN_NAME_TAG = 'id';
    private const FOREIGN_TABLE_NAME_TAG = 'tag';

    private const CONSTRAINT_NAME_SERVER = 'IDX_3D40BDD91844E6B7';
    private const INDEX_NAME_SERVER = 'IDX_3D40BDD91844E6B7';
    private const CONSTRAINT_NAME_TAG = 'IDX_3D40BDD9BAD26311';
    private const INDEX_NAME_TAG = 'IDX_3D40BDD9BAD26311';

    public function getDescription(): string
    {
        return 'Migration for add tags to servers';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME_SERVER, Types::INTEGER)
            ->setNotnull(true);
        $table->addColumn(self::COLUMN_NAME_TAG, Types::INTEGER)
            ->setNotnull(true);
        $table->setPrimaryKey([self::COLUMN_NAME_SERVER,self::COLUMN_NAME_TAG]);


        $table->addForeignKeyConstraint(self::FOREIGN_TABLE_NAME_SERVER, [self::COLUMN_NAME_SERVER], [self::FOREIGN_COLUMN_NAME_SERVER], ['onDelete' => 'CASCADE'], self::CONSTRAINT_NAME_SERVER);
        $table->addIndex([self::COLUMN_NAME_SERVER], self::INDEX_NAME_SERVER);

        $table->addForeignKeyConstraint(self::FOREIGN_TABLE_NAME_TAG, [self::COLUMN_NAME_TAG], [self::FOREIGN_COLUMN_NAME_TAG], ['onDelete' => 'CASCADE'], self::CONSTRAINT_NAME_TAG);
        $table->addIndex([self::COLUMN_NAME_TAG], self::INDEX_NAME_TAG);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->removeForeignKey(self::CONSTRAINT_NAME_TAG);
        $table->removeForeignKey(self::CONSTRAINT_NAME_SERVER);
        $schema->dropTable(self::TABLE_NAME);
//         t;
    }
}
