<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210126184805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server ADD logo_url LONGTEXT DEFAULT NULL, ADD smtp_host LONGTEXT DEFAULT NULL, ADD smtp_port INT DEFAULT NULL, ADD smtp_password LONGTEXT DEFAULT NULL, ADD smtp_username LONGTEXT DEFAULT NULL, ADD smtp_encryption LONGTEXT DEFAULT NULL, ADD smtp_email LONGTEXT DEFAULT NULL, ADD smtp_sender_name LONGTEXT DEFAULT NULL, ADD slug LONGTEXT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server DROP logo_url, DROP smtp_host, DROP smtp_port, DROP smtp_password, DROP smtp_username, DROP smtp_encryption, DROP smtp_email, DROP smtp_sender_name, DROP slug');
        }
    }
}
