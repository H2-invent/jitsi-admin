<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220923130558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE callout_session (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, invited_from_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_937E553554177093 (room_id), INDEX IDX_937E5535A76ED395 (user_id), INDEX IDX_937E55351A2DA7F0 (invited_from_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE callout_session ADD CONSTRAINT FK_937E553554177093 FOREIGN KEY (room_id) REFERENCES rooms (id)');
            $this->addSql('ALTER TABLE callout_session ADD CONSTRAINT FK_937E5535A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE callout_session ADD CONSTRAINT FK_937E55351A2DA7F0 FOREIGN KEY (invited_from_id) REFERENCES fos_user (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE callout_session');
        }
    }
}
