<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241203122804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE uploaded_recording (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, filename VARCHAR(500) NOT NULL, INDEX IDX_3CC9EEAD54177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE uploaded_recordings (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE uploaded_recording ADD CONSTRAINT FK_3CC9EEAD54177093 FOREIGN KEY (room_id) REFERENCES rooms (id)');
        $this->addSql('ALTER TABLE rooms DROP disable_self_subscription_double_opt_in');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE uploaded_recording DROP FOREIGN KEY FK_3CC9EEAD54177093');
        $this->addSql('DROP TABLE uploaded_recording');
        $this->addSql('DROP TABLE uploaded_recordings');
        $this->addSql('ALTER TABLE rooms ADD disable_self_subscription_double_opt_in TINYINT(1) DEFAULT NULL');
    }
}
