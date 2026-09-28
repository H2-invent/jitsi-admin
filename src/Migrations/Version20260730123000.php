<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730123000 extends AbstractMigration
{
    private const string TABLE_LOBBY = 'lobby_waitung_user';
    private const string TABLE_CALLER = 'caller_session';
    private const string FK_LOBBY_CALLER = 'FK_6ABDB21A6D04C84F';
    private const string FK_CALLER_LOBBY = 'FK_AD413A3FB03FB6FB';
    private const string IDX_LOBBY_CALLER = 'UNIQ_6ABDB21A6D04C84F';

    public function getDescription(): string
    {
        return 'Remove the obsolete inverse caller-session column and add ON DELETE SET NULL to the owning FK';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE caller_session cs INNER JOIN lobby_waitung_user lwu ON lwu.caller_session_id = cs.id SET cs.lobby_waiting_user_id = lwu.id WHERE cs.lobby_waiting_user_id IS NULL'
        );

        $lobbyTable = $schema->getTable(self::TABLE_LOBBY);
        $lobbyTable->removeForeignKey(self::FK_LOBBY_CALLER);
        $lobbyTable->dropIndex(self::IDX_LOBBY_CALLER);
        $lobbyTable->dropColumn('caller_session_id');

        $callerTable = $schema->getTable(self::TABLE_CALLER);
        $callerTable->removeForeignKey(self::FK_CALLER_LOBBY);
        $callerTable->addForeignKeyConstraint(
            self::TABLE_LOBBY,
            ['lobby_waiting_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            self::FK_CALLER_LOBBY,
        );
    }

    public function down(Schema $schema): void
    {
        // The data copy has to run between adding the column and adding its index/FK.
        // Explicit addSql() statements are always executed before the schema-diff
        // statements, so this direction uses ordered SQL instead of the schema API.
        $dropCallerFk = sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s',
            self::TABLE_CALLER,
            self::FK_CALLER_LOBBY,
        );
        $this->addSql($dropCallerFk);

        $restoreCallerFk = sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (lobby_waiting_user_id) REFERENCES %s (id)',
            self::TABLE_CALLER,
            self::FK_CALLER_LOBBY,
            self::TABLE_LOBBY,
        );
        $this->addSql($restoreCallerFk);

        $addColumn = sprintf(
            'ALTER TABLE %s ADD caller_session_id INT DEFAULT NULL',
            self::TABLE_LOBBY,
        );
        $this->addSql($addColumn);

        $backfill = sprintf(
            'UPDATE %s lwu INNER JOIN %s cs ON cs.lobby_waiting_user_id = lwu.id SET lwu.caller_session_id = cs.id',
            self::TABLE_LOBBY,
            self::TABLE_CALLER,
        );
        $this->addSql($backfill);

        $createIndex = sprintf(
            'CREATE UNIQUE INDEX %s ON %s (caller_session_id)',
            self::IDX_LOBBY_CALLER,
            self::TABLE_LOBBY,
        );
        $this->addSql($createIndex);

        $restoreLobbyFk = sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (caller_session_id) REFERENCES %s (id) ON DELETE SET NULL',
            self::TABLE_LOBBY,
            self::FK_LOBBY_CALLER,
            self::TABLE_CALLER,
        );
        $this->addSql($restoreLobbyFk);
    }
}
