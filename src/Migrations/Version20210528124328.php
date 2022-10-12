<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210528124328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE `repeat` (id INT AUTO_INCREMENT NOT NULL, repetation INT DEFAULT NULL, repeat_until DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE repeat_user (repeat_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_3949A129CD096AF4 (repeat_id), INDEX IDX_3949A129A76ED395 (user_id), PRIMARY KEY(repeat_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE repeat_user ADD CONSTRAINT FK_3949A129CD096AF4 FOREIGN KEY (repeat_id) REFERENCES `repeat` (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE repeat_user ADD CONSTRAINT FK_3949A129A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE rooms ADD repeater_id INT DEFAULT NULL, ADD repeater_removed TINYINT(1) DEFAULT NULL');
            $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A9626397C6E FOREIGN KEY (repeater_id) REFERENCES `repeat` (id)');
            $this->addSql('CREATE INDEX IDX_7CA11A9626397C6E ON rooms (repeater_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE repeat_user DROP FOREIGN KEY FK_3949A129CD096AF4');
            $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A9626397C6E');
            $this->addSql('DROP TABLE `repeat`');
            $this->addSql('DROP TABLE repeat_user');
            $this->addSql('DROP INDEX IDX_7CA11A9626397C6E ON rooms');
            $this->addSql('ALTER TABLE rooms DROP repeater_id, DROP repeater_removed');
        }
    }
}
