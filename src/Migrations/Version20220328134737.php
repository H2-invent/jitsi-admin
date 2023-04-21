<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220328134737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE caller_session (id INT AUTO_INCREMENT NOT NULL, lobby_waiting_user_id INT NOT NULL, session_id LONGTEXT NOT NULL, created_at DATETIME NOT NULL, auth_ok TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_AD413A3FB03FB6FB (lobby_waiting_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE caller_session ADD CONSTRAINT FK_AD413A3FB03FB6FB FOREIGN KEY (lobby_waiting_user_id) REFERENCES lobby_waitung_user (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE caller_session');
        }
    }
}
