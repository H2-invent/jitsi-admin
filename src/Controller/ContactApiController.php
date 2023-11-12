<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\ParticipantSearchService;
use App\Service\ServerUserManagment;
use App\Service\webhook\RoomStatusFrontendService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ContactApiController extends AbstractController
{
    public function __construct(
        private UserRepository           $userRepository,
        private ParticipantSearchService $participantSearchService,
        private UploaderHelper           $uploaderHelper,
        private ServerUserManagment      $serverUserManagment,
        private TagRepository            $tagRepository,
        private RoomsRepository          $roomsRepository,
    )
    {
    }

    #[Route('/room/contact/api', name: 'app_contact_api')]
    public function index(): Response
    {
        $servers = $this->serverUserManagment->getServersFromUser($this->getUser());
        $serverArr = [];
        if (count($servers) > 0) {
            foreach ($servers as $data) {
                $serverArr[] = ['name' => $data->getServerName(), 'id' => $data->getId()];
            }
        }
        $tags = $this->tagRepository->findBy(['disabled' => false], ['priority' => 'ASC']);
        $tagArr = [];
        if (count($tags) > 0) {
            foreach ($tags as $tag) {
                $tagArr[] = ['name' => $tag->getTitle(), 'id' => $tag->getId()];
            }
        }
        $contact = $this->getUser()->getAddressbook();
        $res = [];


        foreach ($contact as $data) {
            $tmp['name'] = $this->participantSearchService->buildShowInFrontendStringNoString($data);
            $tmp['image'] = $data->getProfilePicture() ? $path = $this->uploaderHelper->asset($data->getProfilePicture(), 'documentFile') : '';
            $tmp['uid'] = $data->getUid();
            $tmp['id'] = $data->getId();
            $res['contacts'][] = $tmp;
        }
        $res['servers'] = $serverArr;
        $res['tags'] = $tagArr;
        return new JsonResponse($res);
    }


    #[Route('/room/fixed_rooms/api', name: 'app_fixed_rooms_api')]
    public function fixedRooms(): Response
    {
        $persistantRooms = $this->roomsRepository->getMyPersistantRooms($this->getUser(), 0);
        $res = [];
        foreach ($persistantRooms as $data) {
            $tmp['name'] = $data->getName();
            $tmp['startUrl'] = $this->generateUrl('room_join',['t'=>'b','room'=>$data->getId()],UrlGenerator::ABSOLUTE_URL);
            $tmp['moderator'] = $this->participantSearchService->buildShowInFrontendStringNoString($data->getModerator());
            $res['rooms'][] = $tmp;
        }
        return new JsonResponse($res);
    }
}
