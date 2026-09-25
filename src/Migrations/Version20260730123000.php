<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
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
        // correlated subquery instead of UPDATE ... JOIN, which PostgreSQL does not support
        $this->addSql(
            'UPDATE caller_session SET lobby_waiting_user_id = (SELECT lwu.id FROM lobby_waitung_user lwu WHERE lwu.caller_session_id = caller_session.id)'
            . ' WHERE lobby_waiting_user_id IS NULL AND EXISTS (SELECT 1 FROM lobby_waitung_user lwu WHERE lwu.caller_session_id = caller_session.id)'
        );

        $lobbyTable = $schema->getTable(self::TABLE_LOBBY);
        $lobbyTable->dropForeignKey(self::FK_LOBBY_CALLER);
        $lobbyTable->dropIndex(self::IDX_LOBBY_CALLER);
        $lobbyTable->dropColumn('caller_session_id');

        $callerTable = $schema->getTable(self::TABLE_CALLER);
        $callerTable->dropForeignKey(self::FK_CALLER_LOBBY);
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
        $callerTable = $schema->getTable(self::TABLE_CALLER);
        $callerTable->dropForeignKey(self::FK_CALLER_LOBBY);
        $callerTable->addForeignKeyConstraint(
            self::TABLE_LOBBY,
            ['lobby_waiting_user_id'],
            ['id'],
            [],
            self::FK_CALLER_LOBBY,
        );

        $lobbyTable = $schema->getTable(self::TABLE_LOBBY);
        $lobbyTable->addColumn('caller_session_id', Types::INTEGER)
            ->setDefault(null)
            ->setNotnull(false);

        // caller_session.lobby_waiting_user_id is unique, so the subquery returns at most one row
        $this->addSql(
            'UPDATE lobby_waitung_user SET caller_session_id = (SELECT cs.id FROM caller_session cs WHERE cs.lobby_waiting_user_id = lobby_waitung_user.id)'
        );

        $lobbyTable->addIndex(['caller_session_id'], self::IDX_LOBBY_CALLER);
        $lobbyTable->addForeignKeyConstraint(
            self::TABLE_CALLER,
            ['caller_session_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            self::FK_LOBBY_CALLER,
        );
    }
}
