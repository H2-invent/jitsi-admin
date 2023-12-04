<?php

namespace App\Service\PublicConference;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PublicConferenceService
{
    public function __construct(private EntityManagerInterface $entityManager, private RequestStack $requestStack, private CallerPrepareService $callerPrepareService)
    {
    }

    public function createNewRoomFromName(string $roomName, Server $server): Rooms
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
            if ($this->requestStack && $this->requestStack->getCurrentRequest()) {
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
