<?php

namespace App\Controller;

use Agence104\LiveKit\EgressServiceClient;
use App\Entity\Recording;
use App\Entity\Rooms;
use App\Repository\RecordingRepository;

use Doctrine\ORM\EntityManagerInterface;
use Livekit\DirectFileOutput;
use Livekit\EncodedFileOutput;
use Livekit\EncodedFileType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class StartEgressController extends AbstractController
{
    public function __construct(
        private RecordingRepository    $recordingRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
    )
    {
    }

    #[Route('/room/start/egress/{uidReal}/{template}', name: 'app_start_egress')]
    public function index(Request $request, ?Rooms $rooms, $template): Response
    {
        if (!$rooms || !$rooms->getServer()->isLiveKitServer() || $this->getUser() !== $rooms->getModerator()) {
            $this->logger->debug('Room not found');
            return new JsonResponse(['error' => true]);

        }
        $egressClient = new EgressServiceClient(
           'https://'. $rooms->getServer()->getUrl(),
            $rooms->getServer()->getAppId(),
            $rooms->getServer()->getAppSecret()
        );
        $recording = $this->recordingRepository->findOneBy(['room' => $rooms, 'user' => $this->getUser()]);
        if (!$recording) {
            $recording = new Recording();
            $recording->setRoom($rooms)
                ->setUser($this->getUser())
                ->setUid(md5(uniqid(rand(), true)))
                ->setCreatedAt(new \DateTimeImmutable());
            try {
                $res = $egressClient->startRoomCompositeEgress(
                    $recording->getRoom()->getUid(),
                    $template,
                    (new EncodedFileOutput())
                        ->setFilepath( '/out/'.$recording->getUid().'.mp4')
                        ->setFileType(EncodedFileType::MP4)
                );

                $recording->setRecordingId($res->getEgressId());
                $this->entityManager->persist($recording);
                $this->entityManager->flush();
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());

                return new JsonResponse(['error' => true, 'message'=>$exception->getMessage()]);
            }
            $this->logger->debug('Recording started ',[$recording]);
            return new JsonResponse(['error' => false, 'recordingId' => $recording->getRecordingId()]);
        } else {
            $this->logger->debug('Recording already exists',[$rooms]);
            return new JsonResponse(['error' => true,'message'=>'Recording already exists']);

        }

    }

    #[Route('/room/stop/egress/{recordingId}', name: 'app_stop_egress')]
    public function stop(Request $request, ?Recording $recording): Response
    {

        if (!$recording || $recording->getUser() !== $this->getUser()) {
            throw new NotFoundHttpException('Recording not found');
        }

        try {
            $egressClient = new EgressServiceClient(
                'https://'.$recording->getRoom()->getServer()->getUrl(),
                $recording->getRoom()->getServer()->getAppId(),
                $recording->getRoom()->getServer()->getAppSecret()
            );


            $egressClient->stopEgress(
                $recording->getRecordingId()
            );
            $recording->setUser(null);
            $this->entityManager->persist($recording);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => true,'message'=>$exception->getMessage()]);
        }


        return new JsonResponse(['error' => false]);
    }
}
