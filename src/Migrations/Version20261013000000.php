<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Give every appointment of an existing series conference its own dial-in room number.
 * Previously the appointments of a series had no separate caller room or the invitation handed
 * out the number of the prototype, so the same number was used for the whole series.
 */
final class Version20261013000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create a caller room with its own dial-in number for every appointment of a series without one';
    }

    public function up(Schema $schema): void
    {
        // Data migration: generate one missing caller room per appointment. Kept on the DBAL connection on
        // purpose as this is a bulk data operation. Using the ORM would run the full unit of work (lifecycle
        // listeners, auto-flush of managed entities) and would couple this immutable migration to the current
        // entity mappings which may drift over time.
        $rooms = $this->connection->fetchAllAssociative(
            'SELECT r.id FROM rooms r LEFT JOIN caller_room cr ON cr.room_id = r.id WHERE r.repeater_id IS NOT NULL AND cr.id IS NULL'
        );
        foreach ($rooms as $room) {
            do {
                $callerId = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $used = (int)$this->connection->fetchOne('SELECT COUNT(*) FROM caller_room WHERE caller_id = ?', [$callerId]);
            } while ($used > 0);
            $this->connection->insert(
                'caller_room',
                [
                    'caller_id' => $callerId,
                    'room_id' => $room['id'],
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // The created rows cannot be told apart from caller rooms that already existed.
    }
}
