<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\LogRepository;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\NewRoomService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class NewRoomServiceTest extends KernelTestCase
{
    private function service(): NewRoomService
    {
        return self::getContainer()->get(NewRoomService::class);
    }

    private function user(string $email): User
    {
        return self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
    }

    public function testNewRoomIsCreatedForModerator(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $request = Request::create('/room/new');

        $room = $this->service()->newRoomService($request, $user);

        self::assertInstanceOf(Rooms::class, $room);
        self::assertSame($user, $room->getModerator());
        self::assertSame($user, $room->getCreator());
        self::assertTrue($room->getUser()->contains($user));
        self::assertNotNull($room->getServer());
        self::assertTrue($user->getServers()->contains($room->getServer()));
    }

    public function testServerCookieIsIgnoredForUserWithoutServer(): void
    {
        self::bootKernel();
        $user = $this->user('test@local4.de');
        self::assertCount(0, $user->getServers());
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);
        self::assertNotNull($server);

        $request = Request::create('/room/new');
        $request->cookies->set('room_server', (string)$server->getId());

        $room = $this->service()->newRoomService($request, $user);

        self::assertInstanceOf(Rooms::class, $room);
        self::assertNull($room->getServer());
    }

    public function testNewRoomUsesServerFakeParameter(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si3']);
        self::assertNotNull($server);
        $request = Request::create('/room/new', 'GET', ['serverfake' => $server->getId()]);

        $room = $this->service()->newRoomService($request, $user);

        self::assertInstanceOf(Rooms::class, $room);
        self::assertSame($server, $room->getServer());
    }

    public function testEditRoomIncrementsSequenceAndSetsUids(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setUidModerator(null)->setUidParticipant(null);
        $sequence = $room->getSequence();
        $request = Request::create('/room/new', 'GET', ['id' => $room->getId()]);

        $result = $this->service()->newRoomService($request, $user);

        self::assertInstanceOf(Rooms::class, $result);
        self::assertSame($room, $result);
        self::assertSame($sequence + 1, $room->getSequence());
        self::assertNotNull($room->getUidModerator());
        self::assertNotNull($room->getUidParticipant());
    }

    public function testEditRoomWithoutPermissionRedirectsToDashboard(): void
    {
        self::bootKernel();
        $user = $this->user('test@local4.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $request = Request::create('/room/new', 'GET', ['id' => $room->getId()]);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        self::getContainer()->get(RequestStack::class)->push($request);

        $result = $this->service()->newRoomService($request, $user);

        self::assertInstanceOf(RedirectResponse::class, $result);
        self::assertNotEmpty($session->getFlashBag()->get('danger'));
    }

    public function testRoomChangedDetectsNoChange(): void
    {
        self::bootKernel();
        $start = new \DateTime('2024-01-01 10:00:00');
        $old = (new Rooms())->setStart($start)->setDuration(60)->setName('Room')->setAgenda('Agenda')->setPersistantRoom(false);
        $new = (new Rooms())->setStart($start)->setDuration(60)->setName('Room')->setAgenda('Agenda')->setPersistantRoom(false);

        self::assertFalse($this->service()->roomChanged($old, $new));
    }

    public function testRoomChangedDetectsStartChange(): void
    {
        self::bootKernel();
        $old = (new Rooms())->setStart(new \DateTime('2024-01-01 10:00:00'));
        $new = (new Rooms())->setStart(new \DateTime('2024-01-01 11:00:00'));

        self::assertTrue($this->service()->roomChanged($old, $new));
    }

    public function testRoomChangedDetectsDurationChange(): void
    {
        self::bootKernel();
        $old = (new Rooms())->setDuration(60);
        $new = (new Rooms())->setDuration(30);

        self::assertTrue($this->service()->roomChanged($old, $new));
    }

    public function testRoomChangedDetectsNameChange(): void
    {
        self::bootKernel();
        $old = (new Rooms())->setName('Room A');
        $new = (new Rooms())->setName('Room B');

        self::assertTrue($this->service()->roomChanged($old, $new));
    }

    public function testRoomChangedDetectsAgendaChange(): void
    {
        self::bootKernel();
        $old = (new Rooms())->setAgenda('Agenda A');
        $new = (new Rooms())->setAgenda('Agenda B');

        self::assertTrue($this->service()->roomChanged($old, $new));
    }

    public function testRoomChangedDetectsPersistantRoomChange(): void
    {
        self::bootKernel();
        $old = (new Rooms())->setPersistantRoom(false);
        $new = (new Rooms())->setPersistantRoom(true);

        self::assertTrue($this->service()->roomChanged($old, $new));
    }

    public function testWriteLogInDatabaseCreatesLogWhenCreatorDiffersFromModerator(): void
    {
        self::bootKernel();
        $editor = $this->user('test@local.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setCreator($this->user('test@local2.de'));
        $logRepo = self::getContainer()->get(LogRepository::class);
        $before = $logRepo->count([]);

        $this->service()->writeLogInDatabase($room, $room, $editor);

        self::assertSame($before + 1, $logRepo->count([]));
        $log = $logRepo->findOneBy(['room' => $room], ['id' => 'DESC']);
        self::assertSame($room, $log->getRoom());
        self::assertSame($editor, $log->getUser());
        $message = json_decode($log->getMessage(), true);
        self::assertSame('room Edit', $message['state']);
        self::assertSame($room->getId(), $message['roomId']);
        self::assertSame($editor->getUid(), $message['userName']);
    }

    public function testWriteLogInDatabaseDoesNothingWhenCreatorIsModerator(): void
    {
        self::bootKernel();
        $moderator = $this->user('test@local.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertSame($room->getCreator(), $room->getModerator());
        $logRepo = self::getContainer()->get(LogRepository::class);
        $before = $logRepo->count([]);

        $this->service()->writeLogInDatabase($room, $room, $moderator);

        self::assertSame($before, $logRepo->count([]));
    }
}
