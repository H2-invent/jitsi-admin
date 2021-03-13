<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210312111020 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE userRoomsAttributes (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, room_id INT NOT NULL, share_display TINYINT(1) DEFAULT NULL, INDEX IDX_F98B4CE4A76ED395 (user_id), INDEX IDX_F98B4CE454177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE userRoomsAttributes ADD CONSTRAINT FK_F98B4CE4A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE userRoomsAttributes ADD CONSTRAINT FK_F98B4CE454177093 FOREIGN KEY (room_id) REFERENCES rooms (id)');
        $this->addSql('ALTER TABLE server ADD feature_enable_by_jwt TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE rooms ADD dissallow_screenshare_global TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE userroomsattributes ADD moderator TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE userroomsattributes ADD private_message TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE rooms ADD dissallow_private_message TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE userRoomsAttributes');
        $this->addSql('ALTER TABLE server DROP feature_enable_by_jwt');
        $this->addSql('ALTER TABLE rooms DROP dissallow_screenshare_global');
        $this->addSql('ALTER TABLE userRoomsAttributes DROP moderator');
        $this->addSql('ALTER TABLE userRoomsAttributes DROP private_message');
        $this->addSql('ALTER TABLE rooms DROP dissallow_private_message');
    }
}
