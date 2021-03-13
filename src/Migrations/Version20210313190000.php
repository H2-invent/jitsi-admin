<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210313190000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE userroomsattributes ADD moderator TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE userroomsattributes ADD private_message TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE rooms ADD dissallow_private_message TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE userRoomsAttributes DROP moderator');
        $this->addSql('ALTER TABLE userRoomsAttributes DROP private_message');
        $this->addSql('ALTER TABLE rooms DROP dissallow_private_message');
    }
}
