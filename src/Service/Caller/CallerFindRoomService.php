<?php

namespace App\Service\Caller;

use App\Entity\CallerRoom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CallerFindRoomService
{
    public function __construct(private readonly UrlGeneratorInterface $urlGen, private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function findRoom(string $id): array
    {
        $caller = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $id]);
        $now    = new \DateTimeImmutable()->getTimestamp();
        if (!$caller) {
            return ['status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => []];
        }

        if ($caller->getRoom()->getStartTimestamp() - 1800 > $now && $caller->getRoom()->getPersistantRoom() !== true) {
            return [
                'status'    => 'HANGUP',
                'reason'    => 'TO_EARLY',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime'   => $caller->getRoom()->getEndTimestamp(),
                'links'     => []
            ];
        }
        if ($caller->getRoom()->getEndTimestamp() < $now && $caller->getRoom()->getPersistantRoom() !== true) {
            return [
                'status'    => 'HANGUP',
                'reason'    => 'TO_LATE',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime'   => $caller->getRoom()->getEndTimestamp(),
                'links'     => []
            ];
        }
        return [
            'status'    => 'ACCEPTED',
            'startTime' => $caller->getRoom()->getStartTimestamp(),
            'endTime'   => $caller->getRoom()->getEndTimestamp(),
            'roomName'  => $caller->getRoom()->getName(),
            //todo hier die url rein
            'links'     => ['pin' => $this->urlGen->generate('caller_pin', ['roomId' => $id])]
        ];
    }
}
