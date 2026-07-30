<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the obsolete inverse caller-session column and add ON DELETE SET NULL to the owning FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE caller_session cs INNER JOIN lobby_waitung_user lwu ON lwu.caller_session_id = cs.id SET cs.lobby_waiting_user_id = lwu.id WHERE cs.lobby_waiting_user_id IS NULL');
        $this->addSql('ALTER TABLE lobby_waitung_user DROP FOREIGN KEY FK_6ABDB21A6D04C84F');
        $this->addSql('DROP INDEX UNIQ_6ABDB21A6D04C84F ON lobby_waitung_user');
        $this->addSql('ALTER TABLE lobby_waitung_user DROP caller_session_id');
        $this->addSql('ALTER TABLE caller_session DROP FOREIGN KEY FK_AD413A3FB03FB6FB');
        $this->addSql('ALTER TABLE caller_session ADD CONSTRAINT FK_AD413A3FB03FB6FB FOREIGN KEY (lobby_waiting_user_id) REFERENCES lobby_waitung_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE caller_session DROP FOREIGN KEY FK_AD413A3FB03FB6FB');
        $this->addSql('ALTER TABLE caller_session ADD CONSTRAINT FK_AD413A3FB03FB6FB FOREIGN KEY (lobby_waiting_user_id) REFERENCES lobby_waitung_user (id)');
        $this->addSql('ALTER TABLE lobby_waitung_user ADD caller_session_id INT DEFAULT NULL');
        $this->addSql('UPDATE lobby_waitung_user lwu INNER JOIN caller_session cs ON cs.lobby_waiting_user_id = lwu.id SET lwu.caller_session_id = cs.id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6ABDB21A6D04C84F ON lobby_waitung_user (caller_session_id)');
        $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21A6D04C84F FOREIGN KEY (caller_session_id) REFERENCES caller_session (id) ON DELETE SET NULL');
    }
}
