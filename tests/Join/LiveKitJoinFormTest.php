<?php

namespace App\Tests\Join;

use App\Entity\Rooms;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LiveKitJoinFormTest extends WebTestCase
{
    /**
     * Guests joining a LiveKit room are not asked for a name: the middleware
     * asks for name and company itself.
     */
    public function testJoinPageOmitsNameFieldForLiveKitRoom(): void
    {
        $client = static::createClient();
        $server = $this->createLiveKitServer();
        $room = $this->createRoom($server);

        $data = base64_encode('uid=' . $room->getUid() . '&email=test@local.de');
        $client->request('GET', '/join?data=' . urlencode($data));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="join_view[uid]"]');
        self::assertSelectorExists('input[name="join_view[email]"]');
        self::assertSelectorNotExists('input[name="join_view[name]"]');
    }

    public function testOwnRoomJoinPageOmitsNameFieldForLiveKitRoom(): void
    {
        $client = static::createClient();
        $server = $this->createLiveKitServer();
        $room = $this->createRoom($server);

        $client->request('GET', '/myRoom/start/' . $room->getUid());

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('input[name="join_my_room[name]"]');
        self::assertSelectorExists('form[name="join_my_room"]');
    }

    private function createLiveKitServer(): Server
    {
        $server = new Server();
        $server->setUrl('livekit-rtc.example.com')
            ->setServerName('LiveKit Test')
            ->setSlug('livekitTest')
            ->setLiveKitServer(true)
            ->setJwtModeratorPosition(0)
            ->setAppId('testID')
            ->setAppSecret('testSecrettestSecrettestSecrettestSecret');
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($server);
        $em->flush();

        return $server;
    }

    private function createRoom(Server $server): Rooms
    {
        $room = new Rooms();
        $room->setServer($server)
            ->setName('livekitJoinRoom')
            ->setUid('livekitJoinUid')
            ->setUidReal('livekitJoinUidReal')
            ->setDuration(0)
            ->setSequence(0)
            ->setPersistantRoom(true)
            ->setTotalOpenRooms(true);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($room);
        $em->flush();

        return $room;
    }
}
