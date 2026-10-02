<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Records the (DC2Type:datetime_immutable) type hint on every DATETIME column that
 * was converted from the mutable `datetime` DBAL type to `datetime_immutable`.
 *
 * The underlying SQL type stays DATETIME; only the Doctrine type-hint comment changes,
 * so no data is read or rewritten.
 */
final class Version20260910221843 extends AbstractMigration
{
    private const COMMENT = '(DC2Type:datetime_immutable)';

    /** @var array<string, list<string>> */
    private const COLUMNS = [
        'rooms' => ['start', 'enddate', 'start_utc', 'end_date_utc'],
        'fos_user' => ['created_at', 'last_login', 'updated_at'],
        'room_status' => ['room_created_at', 'destroyed_at', 'created_at', 'updated_at'],
        'room_status_participant' => ['entered_room_at', 'left_room_at'],
        'repeat' => ['repeat_until', 'start_date'],
        'api_keys' => ['created_at'],
        'caller_id' => ['created_at'],
        'caller_room' => ['created_at'],
        'caller_session' => ['created_at'],
        'callout_session' => ['created_at'],
        'deputy' => ['created_at'],
        'documents' => ['updated_at'],
        'license' => ['valid_until'],
        'lobby_waitung_user' => ['created_at'],
        'log' => ['created_at'],
        'notification' => ['created_at'],
        'predefined_lobby_messages' => ['created_at'],
        'scheduling_time' => ['time'],
        'server' => ['updated_at'],
        'star' => ['created_at'],
        'waitinglist' => ['created_at'],
    ];

    public function getDescription(): string
    {
        return 'Add (DC2Type:datetime_immutable) comment to converted DATETIME columns';
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $tableName => $columns) {
            $table = $schema->getTable($tableName);
            foreach ($columns as $column) {
                $table->modifyColumn($column, ['comment' => self::COMMENT]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::COLUMNS as $tableName => $columns) {
            $table = $schema->getTable($tableName);
            foreach ($columns as $column) {
                $table->modifyColumn($column, ['comment' => null]);
            }
        }
    }
}
