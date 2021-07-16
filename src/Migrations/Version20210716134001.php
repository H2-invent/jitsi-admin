<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210716134001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE addressgroup (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4DA9E435A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE addressgroup_user (addressgroup_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_7314106CD1F9CFA2 (addressgroup_id), INDEX IDX_7314106CA76ED395 (user_id), PRIMARY KEY(addressgroup_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE addressgroup ADD CONSTRAINT FK_4DA9E435A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE addressgroup_user ADD CONSTRAINT FK_7314106CD1F9CFA2 FOREIGN KEY (addressgroup_id) REFERENCES addressgroup (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE addressgroup_user ADD CONSTRAINT FK_7314106CA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fos_user DROP FOREIGN KEY FK_957A64796341294B');
        $this->addSql('DROP INDEX IDX_957A64796341294B ON fos_user');
        $this->addSql('ALTER TABLE fos_user DROP my_own_room_server_id, DROP own_room_uid');
        $this->addSql('ALTER TABLE rooms DROP persistant_room, DROP slug, DROP total_open_rooms, DROP total_open_rooms_open_time');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addressgroup_user DROP FOREIGN KEY FK_7314106CD1F9CFA2');
        $this->addSql('DROP TABLE addressgroup');
        $this->addSql('DROP TABLE addressgroup_user');
        $this->addSql('ALTER TABLE fos_user ADD my_own_room_server_id INT DEFAULT NULL, ADD own_room_uid LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A64796341294B FOREIGN KEY (my_own_room_server_id) REFERENCES server (id)');
        $this->addSql('CREATE INDEX IDX_957A64796341294B ON fos_user (my_own_room_server_id)');
        $this->addSql('ALTER TABLE rooms ADD persistant_room TINYINT(1) DEFAULT NULL, ADD slug LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD total_open_rooms TINYINT(1) DEFAULT NULL, ADD total_open_rooms_open_time INT DEFAULT NULL');
    }
}
