<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220220142211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE documents (id INT AUTO_INCREMENT NOT NULL, document_file_name VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE fos_user ADD profile_picture_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479292E8AE2 FOREIGN KEY (profile_picture_id) REFERENCES documents (id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479292E8AE2 ON fos_user (profile_picture_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE fos_user DROP FOREIGN KEY FK_957A6479292E8AE2');
            $this->addSql('DROP TABLE documents');
            $this->addSql('DROP INDEX UNIQ_957A6479292E8AE2 ON fos_user');
            $this->addSql('ALTER TABLE fos_user DROP profile_picture_id');
        }
    }
}
