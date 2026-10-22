<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203102912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rooms ADD original_server_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A9672D799A2 FOREIGN KEY (original_server_id) REFERENCES server (id)');
        $this->addSql('CREATE INDEX IDX_7CA11A9672D799A2 ON rooms (original_server_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A9672D799A2');
        $this->addSql('DROP INDEX IDX_7CA11A9672D799A2 ON rooms');
        $this->addSql('ALTER TABLE rooms DROP original_server_id');
    }
}
