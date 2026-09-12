<?php

namespace App\Tests\Service\livekit;

use App\Entity\Recording;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\livekit\EgressService;
use Doctrine\ORM\EntityManagerInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Livekit\EgressInfo;
use Livekit\RoomCompositeEgressRequest;
use Livekit\StopEgressRequest;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MockHttpClientStrategy implements DiscoveryStrategy
{
    public static ?ClientInterface $client = null;

    public static function getCandidates($type): array
    {
        if ($type === ClientInterface::class) {
            return [['class' => static fn() => self::$client]];
        }

        return [];
    }
}

class EgressServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private RoomsRepository $roomsRepository;
    private UserRepository $userRepository;
    private EgressService $service;

    /** @var string[] */
    private array $originalStrategies = [];

    /** @var RequestInterface[] */
    private array $requests = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->roomsRepository = $container->get(RoomsRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->service = $container->get(EgressService::class);

        $this->originalStrategies = array_values(iterator_to_array(Psr18ClientDiscovery::getStrategies()));
        $this->requests = [];
        MockHttpClientStrategy::$client = null;
    }

    protected function tearDown(): void
    {
        Psr18ClientDiscovery::setStrategies($this->originalStrategies);
        MockHttpClientStrategy::$client = null;
        parent::tearDown();
    }

    private function installHttpClient(?callable $handler = null): MockObject
    {
        $this->requests = [];
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(function (RequestInterface $request) use ($handler) {
            $this->requests[] = $request;
            if ($handler) {
                return $handler($request);
            }

            return new Response(200, [], (new EgressInfo())->setEgressId('egress-123')->serializeToString());
        });

        MockHttpClientStrategy::$client = $client;
        Psr18ClientDiscovery::prependStrategy(MockHttpClientStrategy::class);

        return $client;
    }

    private function createRecording(string $recordingId): Recording
    {
        $room = $this->roomsRepository->findOneBy(['name' => 'TestMeeting: 1']);
        $user = $this->userRepository->findOneBy(['email' => 'test@local.de']);

        $recording = (new Recording())
            ->setRoom($room)
            ->setUser($user)
            ->setUid('recording-uid-' . $recordingId)
            ->setRecordingId($recordingId)
            ->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($recording);
        $this->entityManager->flush();

        return $recording;
    }

    public function testStartEgressCreatesRecordingAndReturnsEgressId(): void
    {
        $this->installHttpClient();
        $room = $this->roomsRepository->findOneBy(['name' => 'TestMeeting: 1']);
        $user = $this->userRepository->findOneBy(['email' => 'test@local.de']);

        $result = $this->service->startEgress($room, $user, 'composite');

        self::assertSame(['error' => false, 'recordingId' => 'egress-123'], $result);
        self::assertCount(1, $this->requests);

        $request = $this->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame(
            'https://' . $room->getServer()->getUrl() . '/twirp/livekit.Egress/StartRoomCompositeEgress',
            (string) $request->getUri()
        );
        self::assertStringStartsWith('Bearer ', $request->getHeaderLine('Authorization'));

        $payload = new RoomCompositeEgressRequest();
        $payload->mergeFromString((string) $request->getBody());
        self::assertSame($room->getUid() . '@localhost:8000', $payload->getRoomName());
        self::assertSame('composite', $payload->getLayout());

        $recording = $this->entityManager->getRepository(Recording::class)->findOneBy([
            'room' => $room,
            'user' => $user,
        ]);
        self::assertInstanceOf(Recording::class, $recording);
        self::assertSame('egress-123', $recording->getRecordingId());
        self::assertSame('/out/' . $recording->getUid() . '.mp4', $payload->getFileOutputs()[0]->getFilepath());
    }

    public function testStartEgressReturnsErrorWhenRecordingAlreadyExists(): void
    {
        $client = $this->installHttpClient();
        $client->expects($this->never())->method('sendRequest');

        $room = $this->roomsRepository->findOneBy(['name' => 'TestMeeting: 1']);
        $user = $this->userRepository->findOneBy(['email' => 'test@local.de']);
        $this->createRecording('already-running');

        $result = $this->service->startEgress($room, $user, 'composite');

        self::assertSame(['error' => true, 'message' => 'Recording already exists'], $result);
    }

    public function testStopEgressStopsRecordingAndClearsUser(): void
    {
        $this->installHttpClient(function (RequestInterface $request) {
            return new Response(200, [], (new EgressInfo())->setEgressId('stopped')->serializeToString());
        });
        $recording = $this->createRecording('egress-to-stop');
        $recordingId = $recording->getId();

        $result = $this->service->stopEgress($recording);

        self::assertSame(['error' => false], $result);
        self::assertCount(1, $this->requests);
        self::assertSame(
            'https://' . $recording->getRoom()->getServer()->getUrl() . '/twirp/livekit.Egress/StopEgress',
            (string) $this->requests[0]->getUri()
        );

        $payload = new StopEgressRequest();
        $payload->mergeFromString((string) $this->requests[0]->getBody());
        self::assertSame('egress-to-stop', $payload->getEgressId());

        $stored = $this->entityManager->find(Recording::class, $recordingId);
        self::assertNull($stored->getUser());
    }

    public function testStopAllEgressStopsEveryRecordingWithUser(): void
    {
        $this->installHttpClient(function (RequestInterface $request) {
            return new Response(200, [], (new EgressInfo())->setEgressId('stopped')->serializeToString());
        });

        $room = $this->roomsRepository->findOneBy(['name' => 'TestMeeting: 1']);
        $user = $this->userRepository->findOneBy(['email' => 'test@local.de']);
        foreach (['first', 'second'] as $suffix) {
            $recording = (new Recording())
                ->setRoom($room)
                ->setUser($user)
                ->setUid('stop-all-' . $suffix)
                ->setRecordingId('egress-' . $suffix)
                ->setCreatedAt(new \DateTimeImmutable());
            $this->entityManager->persist($recording);
            $room->addLiveKitRecording($recording);
        }
        $this->entityManager->flush();

        $this->service->stopAllEgress($room);

        self::assertCount(2, $this->requests);
        foreach ($this->requests as $request) {
            self::assertStringEndsWith('/twirp/livekit.Egress/StopEgress', (string) $request->getUri());
        }
        foreach ($room->getLiveKitRecordings() as $recording) {
            self::assertNull($recording->getUser());
        }
    }

    public function testStopAllEgressWithNullRoomDoesNothing(): void
    {
        $client = $this->installHttpClient();
        $client->expects($this->never())->method('sendRequest');

        $this->service->stopAllEgress(null);

        self::assertCount(0, $this->requests);
    }

    public function testStopAllEgressSkipsRecordingsWithoutUser(): void
    {
        $client = $this->installHttpClient();
        $client->expects($this->never())->method('sendRequest');

        $room = $this->roomsRepository->findOneBy(['name' => 'TestMeeting: 1']);
        $recording = (new Recording())
            ->setRoom($room)
            ->setUser(null)
            ->setUid('no-user-recording')
            ->setRecordingId('egress-orphan')
            ->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($recording);
        $room->addLiveKitRecording($recording);
        $this->entityManager->flush();

        $this->service->stopAllEgress($room);

        self::assertCount(0, $this->requests);
    }
}
