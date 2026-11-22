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
        $lobbyUserOfSession = $this->connection->createQueryBuilder()
            ->select('lwu.id')
            ->from(self::TABLE_LOBBY, 'lwu')
            ->where('lwu.caller_session_id = ' . self::TABLE_CALLER . '.id')
            ->getSQL()
        ;

        $this->addSql(
            $this->connection->createQueryBuilder()
                ->update(self::TABLE_CALLER)
                ->set('lobby_waiting_user_id', '(' . $lobbyUserOfSession . ')')
                ->where('lobby_waiting_user_id IS NULL')
                ->andWhere('EXISTS (' . $lobbyUserOfSession . ')')
                ->getSQL()
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

        $lobbyTable->addIndex(['caller_session_id'], self::IDX_LOBBY_CALLER);
        $lobbyTable->addForeignKeyConstraint(
            self::TABLE_CALLER,
            ['caller_session_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            self::FK_LOBBY_CALLER,
        );
    }

    public function postDown(Schema $schema): void
    {
        // Runs after the schema changes of down(): caller_session_id only exists again at this point.
        $sessionOfLobbyUser = $this->connection->createQueryBuilder()
            ->select('cs.id')
            ->from(self::TABLE_CALLER, 'cs')
            ->where('cs.lobby_waiting_user_id = ' . self::TABLE_LOBBY . '.id')
            ->getSQL()
        ;

        $this->connection->createQueryBuilder()
            ->update(self::TABLE_LOBBY)
            ->set('caller_session_id', '(' . $sessionOfLobbyUser . ')')
            ->executeStatement()
        ;
    }
}
