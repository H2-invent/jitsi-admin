<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230513114004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }


    private const TABLE_NAME = 'scheduling_time';
    private const COLUMN_NAME = 'created_from_id';
    private const FOREIGN_COLUMN_NAME = 'id';
    private const FOREIGN_TABLE_NAME = 'fos_user';
    private const CONSTRAINT_NAME = 'IDX_6B3A7EB43EA4CB4D';
    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $foreignTable = $schema->getTable(self::FOREIGN_TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME, Types::INTEGER)
            ->setDefault(null)
            ->setNotnull(false);
        $table->addForeignKeyConstraint(self::FOREIGN_TABLE_NAME, [self::COLUMN_NAME],[self::FOREIGN_COLUMN_NAME],[],self::CONSTRAINT_NAME);
        $table->addIndex([self::COLUMN_NAME],self::CONSTRAINT_NAME);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->removeForeignKey(self::CONSTRAINT_NAME);
        $table->dropIndex(self::CONSTRAINT_NAME);
        $table->dropColumn(self::COLUMN_NAME);
    }
}
