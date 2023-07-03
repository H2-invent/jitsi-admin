<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220323074215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server ADD cors_header TINYINT(1) DEFAULT NULL');
            if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%app:cron:sendReminder%'")->fetchOne() == 0) {
                $this->addSql('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("sendReminder","app:cron:sendReminder","*/10 * * * *","send reminder","1")');
            }
            if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%app:index:user%'")->fetchOne() == 0) {
                $this->addSql('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("indexUser","app:index:user","0 * * * *","Reindex user","1")');
            }
            if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%app:lobby:cleanUp%'")->fetchOne() == 0) {
                $this->addSql('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("cleanLobby"," app:lobby:cleanUp  2","0 * * * *","Clean up the Lobby","1")');
            }
            if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%cron:reports:truncate%'")->fetchOne() == 0) {
                $this->addSql('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("cleanReports"," cron:reports:truncate 10","0 * * * *","Clean up Repots","1")');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server DROP cors_header');
        }
    }
}
