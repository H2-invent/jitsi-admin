<?php

namespace App\Service\livekit;

use Agence104\LiveKit\EgressServiceClient;
use App\Entity\Recording;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RecordingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Livekit\EncodedFileOutput;
use Livekit\EncodedFileType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class EgressService
{
    public function __construct(
        private RecordingRepository    $recordingRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
    )
    {
    }

    public function startEgress(Rooms $rooms, User $user, $template)
    {
        $recording = $this->recordingRepository->findOneBy(['room' => $rooms, 'user' => $user]);
        if (!$recording) {
            $recording = new Recording();
            $recording->setRoom($rooms)
                ->setUser($user)
                ->setUid(md5(uniqid(rand(), true)))
                ->setCreatedAt(new \DateTimeImmutable());
            try {
                $egressClient = new EgressServiceClient(
                    'https://'. $rooms->getServer()->getUrl(),
                    $rooms->getServer()->getAppId(),
                    $rooms->getServer()->getAppSecret()
                );
                $res = $egressClient->startRoomCompositeEgress(
                    $recording->getRoom()->getUid(),
                    $template,
                    (new EncodedFileOutput())
                        ->setFilepath('/out/' . $recording->getUid() . '.mp4')
                        ->setFileType(EncodedFileType::MP4)
                );

                $recording->setRecordingId($res->getEgressId());
                $this->entityManager->persist($recording);
                $this->entityManager->flush();
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());

                return ['error' => true, 'message' => $exception->getMessage()];
            }
            $this->logger->debug('Recording started ', [$recording]);
            return ['error' => false, 'recordingId' => $recording->getRecordingId()];
        } else {
            $this->logger->debug('Recording already exists', [$rooms]);
            return ['error' => true, 'message' => 'Recording already exists'];

        }
    }
    public function stopAllEgress(Rooms $rooms):void
    {
        try {
            foreach ($rooms->getLiveKitRecordings() as $liveKitRecording) {
                if ($liveKitRecording->getUser()){
                    $this->stopEgress($liveKitRecording);
                }
            }
        }catch (\Exception $exception){

        }

    }
    public function stopEgress(Recording $recording) {
        try {
            $egressClient = new EgressServiceClient(
                'https://'.$recording->getRoom()->getServer()->getUrl(),
                $recording->getRoom()->getServer()->getAppId(),
                $recording->getRoom()->getServer()->getAppSecret()
            );

            $egressClient->stopEgress(
                $recording->getRecordingId()
            );
        } catch (\Exception $exception) {
            return ['error' => true,'message'=>$exception->getMessage()];
        } finally {
            $recording->setUser(null);
            $this->entityManager->persist($recording);
            $this->entityManager->flush();
        }


        return ['error' => false];
    }

}