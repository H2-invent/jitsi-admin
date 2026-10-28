<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Enums\UidType;
use App\Helper\UidGeneratorHelper;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rotate every legacy uid value (md5(uniqid()) or the weak random_int/time variant) to the
 * UidGeneratorHelper format, so previously guessable capability tokens are re-rolled.
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
        return 'Rotate legacy weak uids to UidGeneratorHelper format';
    }

    public function up(Schema $schema): void
    {
        $this->rotate(
            'rooms',
            'uid',
            UidType::ROOM,
            '^[0-9a-f]{32}$|^[0-9]{11,12}$',
            'moderator_id IS NOT NULL'
        );
        $this->rotate('rooms', 'uid_real', UidType::ROOM);
        $this->rotate('rooms', 'uid_moderator', UidType::MODERATOR);
        $this->rotate('rooms', 'uid_participant', UidType::PARTICIPANT);
        $this->rotate('fos_user', 'uid', UidType::USER);
        $this->rotate('fos_user', 'own_room_uid', UidType::OWN_ROOM);
        $this->rotate('subscriber', 'uid', UidType::SUBSCRIBER);
        $this->rotate('scheduling', 'uid', UidType::SCHEDULE);
        $this->rotate('`repeat`', 'uid', UidType::REPEATER);
        $this->rotate('callout_session', 'uid', UidType::CALLOUT);
        $this->rotate('lobby_waitung_user', 'uid', UidType::USER);
        $this->rotate('caller_session', 'message_uid', UidType::MESSAGE);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The old weak uid values cannot be reconstructed.');
    }

    private function rotate(
        string $table,
        string $column,
        UidType $type,
        string $pattern = '^[0-9a-f]{32}$',
        string $extraWhere = ''
    ): void {
        $where = sprintf("%s IS NOT NULL AND %s REGEXP '%s'", $column, $column, $pattern);
        if ($extraWhere !== '') {
            $where .= ' AND ' . $extraWhere;
        }

        $rows = $this->connection->fetchAllAssociative(
            sprintf('SELECT id, %s AS uid_value FROM %s WHERE %s', $column, $table, $where)
        );

        foreach ($rows as $row) {
            $this->addSql(
                sprintf('UPDATE %s SET %s = ? WHERE id = ?', $table, $column),
                [UidGeneratorHelper::generate($type), $row['id']]
            );
        }
    }
}
