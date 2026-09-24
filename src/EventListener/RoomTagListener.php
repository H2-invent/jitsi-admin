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
 * Here we send updates via mercure if any tag has changed so we can update the multiframe window accordingly.
 * We queue the rooms we should update first and send only on flush so we don't break any DB writes when mercure fails.
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

    public function postUpdate(Rooms $room, PostUpdateEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($room);

        if (array_key_exists('tag', $changeSet)) {
            $this->queueRoom($room);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->roomsToPublish === []) {
            return;
        }

        foreach ($this->roomsToPublish as $id => $room) {
            $this->directSendService->sendRoomTag($room);
            unset($this->roomsToPublish[$id]);
        }
    }

    private function queueRoom(Rooms $room): void
    {
        if (!in_array($room, $this->roomsToPublish, true)) {
            $this->roomsToPublish[] = $room;
        }
    }
}

