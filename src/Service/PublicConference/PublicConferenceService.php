<?php

namespace App\Service\PublicConference;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\Caller\CallerPinService;
use App\Service\Caller\CallerPrepareService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PublicConferenceService
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly RequestStack $requestStack, private readonly CallerPrepareService $callerPrepareService)
    {
    }

    public function createNewRoomFromName(string $roomName, ?Server $server = null): Rooms
    {
        $roomname = UtilsHelper::slugify($roomName);
        $uid = md5($server->getUrl() . $roomname);
        $room = $this->entityManager->getRepository(Rooms::class)->findOneBy(['uid' => $uid, 'moderator' => null]);
        $tags = $server->getTag()->toArray();
        if (!$room) {
            $room = new Rooms();
            $room->setServer($server)
                ->setUid($uid)
                ->setName($roomname)
                ->setDuration(0)
                ->setSequence(0)
                ->setPersistantRoom(true)
                ->setUidReal(md5(uniqid()));
            if ($this->requestStack->getCurrentRequest()) {
                $room->setHostUrl($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost());
            }
            $this->entityManager->persist($room);
            $this->entityManager->flush();
            $this->callerPrepareService->addCallerIdToRoom($room);
        }
        if (count($tags) === 1){
            $room->setTag($tags[0]);
            $this->entityManager->persist($room);
            $this->entityManager->flush();
        }
        return $room;
    }
}
