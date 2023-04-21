<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220923130800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE SEQUENCE callout_session_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
            $this->addSql('CREATE TABLE callout_session (id INT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, invited_from_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_937E553554177093 ON callout_session (room_id)');
            $this->addSql('CREATE INDEX IDX_937E5535A76ED395 ON callout_session (user_id)');
            $this->addSql('CREATE INDEX IDX_937E55351A2DA7F0 ON callout_session (invited_from_id)');
            $this->addSql('ALTER TABLE callout_session ADD CONSTRAINT FK_937E553554177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE callout_session ADD CONSTRAINT FK_937E5535A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE callout_session ADD CONSTRAINT FK_937E55351A2DA7F0 FOREIGN KEY (invited_from_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP SEQUENCE callout_session_id_seq CASCADE');
            $this->addSql('DROP TABLE callout_session');
        }
    }
}
