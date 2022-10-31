<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221031173040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE deputy_manager (user_source INT NOT NULL, user_target INT NOT NULL, PRIMARY KEY(user_source, user_target))');
            $this->addSql('CREATE INDEX IDX_B4C5243C3AD8644E ON deputy_manager (user_source)');
            $this->addSql('CREATE INDEX IDX_B4C5243C233D34C1 ON deputy_manager (user_target)');
            $this->addSql('ALTER TABLE deputy_manager ADD CONSTRAINT FK_B4C5243C3AD8644E FOREIGN KEY (user_source) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE deputy_manager ADD CONSTRAINT FK_B4C5243C233D34C1 FOREIGN KEY (user_target) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        }
    }


    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE deputy_manager');
        }
    }
}
