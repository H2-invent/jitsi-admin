<?php

namespace App\Service\api;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LicenseService;
use App\Service\livekit\SipTrunkGenerator;
use App\Service\RoomService;
use App\Service\webhook\RoomStatusFrontendService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ConferenceMapperService
{
    public function __construct(
        private RoomStatusFrontendService $roomStatusFrontendService,
        private RoomService               $roomService,
        private UserRepository            $userRepository,
        private ParameterBagInterface     $parameterBag,
        private HttpClientInterface       $httpClient,
        private LoggerInterface           $logger,
        private SipTrunkGenerator         $sipTrunkGenerator
    )
    {
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $apiKey
     * @param string|null $callerId
     * @return array<string, mixed>
     */
    public function checkConference(?CallerRoom $callerRoom, string $apiKey, ?string $callerId): array
    {
        /** @var string $showNameInConference */
        $showNameInConference = $this->parameterBag->get('laf_showNameInConference');

        if (!$callerRoom) {
            return ['error' => true, 'reason' => 'ROOM_NOT_FOUND'];
        }
        $room = $callerRoom->getRoom();
        $started = $this->findRoomStatusFromOtherServer($room, $apiKey);
        if (!$started) {
            $started = $this->roomStatusFrontendService->isRoomCreated($room);

            // skip beyond "Bearer "
            $apiKey = substr($apiKey, 7);
            $server = $room->getServer();
            if (!$server) {
                return ['error' => true, 'text' => 'NO_SERVER_FOUND'];
            }
            if ($apiKey !== $server->getApiKey()) {
                return ['error' => true, 'text' => 'AUTHORIZATION_FAILED'];
            }

            if (!$started) {
                return [
                    'state' => 'WAITING',
                    'reason' => 'NOT_STARTED'
                ];
            }
        }
        $user = null;
        if ($callerId){
            $user = $this->findNameFromCallerId(callerId: $callerId);
        }


        $res = [
            'state' => 'STARTED',
            'jwt' => $this->roomService->generateJwt($room, null, $user ? $user->getFormatedName($showNameInConference) : $callerId),
            'room_name' => $room->getUid() . '@' . $room->getServer()->getJigasiProsodyDomain(),
            'display_name' => $user ? $user->getFormatedName($showNameInConference) : $callerId
        ];
        if ($room->getServer()->isLiveKitServer()) {
            try {
                $res['sip_trunk'] = $this->sipTrunkGenerator->createNewSIPNumber($room,$callerId);
            }catch (\Exception $exception){
                $res['sip_trunk'] = 'error during fetching sip trunk from livekit';
            }
        }
        return  $res;
    }

    /**
     * @param string $callerId
     */
    public function findNameFromCallerId(string $callerId): ?User
    {
        $this->logger->debug('Caller id fetched to find user', ['callerid' => $callerId]);
        $user = $this->userRepository->findUsersByCallerId(callerId: $callerId);
        return $user;
    }

    public function findRoomStatusFromOtherServer(Rooms $room, string $token): bool
    {
        if ($room->getServer()->getJitsiEventSyncUrl()) {
            $url = $room->getServer()->getJitsiEventSyncUrl() . '/api/v1/event/sync/?room_uid=' . $room->getUid();
            $response = $this->httpClient->request(method: 'GET', url: $url, options: [
                'headers' => [
                    'Authorization' => $token
                ]
            ]);
            $content = $response->toArray();

            // Überprüfe den Status und gib entsprechend true oder false zurück
            if (isset($content['status'])) {
                if ($content['status'] === 'ROOM_STARTED') {
                    return true;
                } elseif ($content['status'] === 'ROOM_CLOSED') {
                    return false;
                }
            }
        }
        return false;
    }
}
