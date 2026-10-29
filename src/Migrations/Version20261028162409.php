<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Helper\UidGeneratorHelper;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rotate every legacy uid value (md5(uniqid()) or the weak random_int/time variant) to a UUID v4,
 * so previously guessable capability tokens are re-rolled.
 *
 * Uids deliberately NOT migrated, with the reason rotation cannot touch them:
 * - rooms.uid with moderator_id IS NULL (public/fast conferences): derived natural key
 *   md5(serverUrl + roomName). The /m/{confId} join flow recomputes it from the room name to
 *   find the room, so rotating it would orphan the historical room (a duplicate is created on
 *   the next visit) without reducing guessability — the room name is public in the URL.
 * - caller_session.session_id: session identifier of the live SIP caller; rotating breaks
 *   active calls.
 * - uploaded_recording.filename and recording.uid: file/path references — the physical file on
 *   the recording filesystem and the LiveKit egress output path /out/{uid}.mp4 are named after
 *   them; rotating the DB value without renaming the file breaks download.
 * - fos_user.calendly_secret: external secret for Calendly webhook verification (no in-repo
 *   consumer); rotating invalidates the configured webhook until it is re-synced.
 * - fos_user.register_id: external account-activation identifier (no in-repo consumer);
 *   rotating breaks outstanding activation links.
 *
 * Values that do not match the legacy formats (already-new uids, fixture literals) are also
 * left untouched.
 */
final class Version20261028162409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rotate legacy weak uids to UUID v4';
    }

    public function up(Schema $schema): void
    {
        $this->rotate(
            'rooms',
            'uid',
            '^[0-9a-f]{32}$|^[0-9]{11,12}$',
            'moderator_id IS NOT NULL'
        );
        $this->rotate('rooms', 'uid_real');
        $this->rotate('rooms', 'uid_moderator');
        $this->rotate('rooms', 'uid_participant');
        $this->rotate('fos_user', 'uid');
        $this->rotate('fos_user', 'own_room_uid');
        $this->rotate('subscriber', 'uid');
        $this->rotate('scheduling', 'uid');
        $this->rotate('`repeat`', 'uid');
        $this->rotate('callout_session', 'uid');
        $this->rotate('lobby_waitung_user', 'uid');
        $this->rotate('caller_session', 'message_uid');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The old weak uid values cannot be reconstructed.');
    }

    private function rotate(
        string $table,
        string $column,
        string $pattern = '^[0-9a-f]{32}$',
        string $extraWhere = ''
    ): void {
        $quotedTable = $this->connection->quoteSingleIdentifier(str_replace('`', '', $table));
        // MySQL uses REGEXP, PostgreSQL uses the POSIX operator ~ for the same pattern.
        $regexOperator = $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform ? '~' : 'REGEXP';

        $where = sprintf("%s IS NOT NULL AND %s %s '%s'", $column, $column, $regexOperator, $pattern);
        if ($extraWhere !== '') {
            $where .= ' AND ' . $extraWhere;
        }

        $rows = $this->connection->fetchAllAssociative(
            sprintf('SELECT id, %s AS uid_value FROM %s WHERE %s', $column, $quotedTable, $where)
        );

        foreach ($rows as $row) {
            $this->addSql(
                sprintf('UPDATE %s SET %s = ? WHERE id = ?', $quotedTable, $column),
                [UidGeneratorHelper::generate(), $row['id']]
            );
        }
    }
}
