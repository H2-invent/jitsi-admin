<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250211155608 extends AbstractMigration
{
    private const TABLE_NAME = 'fos_user';
    private const COLUMN_NAME = 'calendly_server_id';
    private const INDEX_NR='IDX_957A6479E6A6EB57';
    private const FOREIGN_CONSTRAINT='FK_957A6479E6A6EB57';
    private const FOREIGN_TABLE_NAME='server';
    private const FOREIGN_COLUM_NAME='id';

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn(self::COLUMN_NAME,Types::INTEGER)
            ->setDefault(null)
            ->setNotnull(false);

        $table->addForeignKeyConstraint(self::FOREIGN_TABLE_NAME, [self::COLUMN_NAME], [self::FOREIGN_COLUM_NAME],  [], self::FOREIGN_CONSTRAINT);
        $table->addIndex([self::COLUMN_NAME],self::INDEX_NR);

    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);
        $table->removeForeignKey(self::FOREIGN_CONSTRAINT);
        $table->dropIndex(self::INDEX_NR);
        $table->dropColumn(self::COLUMN_NAME);

    }
//    public function up(Schema $schema): void
//    {
//        // this up() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE fos_user ADD calendly_server_id INT DEFAULT NULL');
//        $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479E6A6EB57 FOREIGN KEY (calendly_server_id) REFERENCES server (id)');
//        $this->addSql('CREATE INDEX IDX_957A6479E6A6EB57 ON fos_user (calendly_server_id)');
//    }
//
//    public function down(Schema $schema): void
//    {
//        // this down() migration is auto-generated, please modify it to your needs
//        $this->addSql('ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479E6A6EB57');
//        $this->addSql('DROP INDEX IDX_957A6479E6A6EB57 ON fos_user');
//        $this->addSql('ALTER TABLE fos_user DROP calendly_server_id');
//    }
}
