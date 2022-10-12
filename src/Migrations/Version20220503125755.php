<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220503125755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT NOT NULL, disabled TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE rooms ADD tag_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A96BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
            $this->addSql('CREATE INDEX IDX_7CA11A96BAD26311 ON rooms (tag_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A96BAD26311');
            $this->addSql('DROP TABLE tag');
            $this->addSql('DROP INDEX IDX_7CA11A96BAD26311 ON rooms');
            $this->addSql('ALTER TABLE rooms DROP tag_id');
        }
    }
}
