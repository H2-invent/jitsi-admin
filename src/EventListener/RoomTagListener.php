<?php

namespace App\EventListener;

use App\Entity\Rooms;
use App\Service\Lobby\DirectSendService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Detects changes to the {@see Rooms::$tag} association during a Doctrine flush
 * and automatically publishes the (new or cleared) tag to the room's websocket
 * topic. This way no code path has to remember to trigger the update manually.
 *
 * The per-entity {@see Events::postUpdate} event collects the affected rooms
 * (using the UnitOfWork change set) while the entity graph is still consistent.
 * The {@see Events::postFlush} event then publishes the collected rooms once the
 * transaction has been fully written.
 */
#[AsEntityListener(event: Events::postUpdate, entity: Rooms::class)]
#[AsDoctrineListener(event: Events::postFlush)]
class RoomTagListener
{
    /** @var Rooms[] */
    private array $roomsToPublish = [];

    public function __construct(private readonly DirectSendService $directSendService)
    {
    }

    /**
     * Fires once per updated Rooms entity during the flush. If the tag
     * association changed, the room is queued for publishing.
     */
    public function postUpdate(Rooms $room, PostUpdateEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($room);

        if (array_key_exists('tag', $changeSet)) {
            $this->queueRoom($room);
        }
    }

    /**
     * Fires once after the flush finished (transaction committed). Publishes the
     * collected rooms. sendUpdate() swallows Mercure failures, so publishing here
     * cannot break the surrounding transaction lifecycle.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->roomsToPublish === []) {
            return;
        }

        $rooms = $this->roomsToPublish;
        $this->roomsToPublish = [];

        foreach ($rooms as $room) {
            $this->directSendService->sendRoomTag($room);
        }
    }

    private function queueRoom(Rooms $room): void
    {
        if (!in_array($room, $this->roomsToPublish, true)) {
            $this->roomsToPublish[] = $room;
        }
    }
}

