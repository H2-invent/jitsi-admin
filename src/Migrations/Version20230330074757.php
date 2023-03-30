<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230330074757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE addressbook_favorites (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_D3FE5EA53AD8644E (user_source), INDEX IDX_D3FE5EA5233D34C1 (user_target), PRIMARY KEY(user_source, user_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE addressbook_favorites ADD CONSTRAINT FK_D3FE5EA53AD8644E FOREIGN KEY (user_source) REFERENCES fos_user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE addressbook_favorites ADD CONSTRAINT FK_D3FE5EA5233D34C1 FOREIGN KEY (user_target) REFERENCES fos_user (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE addressbook_favorites DROP FOREIGN KEY FK_D3FE5EA53AD8644E');
            $this->addSql('ALTER TABLE addressbook_favorites DROP FOREIGN KEY FK_D3FE5EA5233D34C1');
            $this->addSql('DROP TABLE addressbook_favorites');
        }
    }
}
