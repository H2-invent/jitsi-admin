<?php

namespace App\Tests\Join;

use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\StartMeetingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StartMeetingControllerTest extends WebTestCase
{
    public function testRoomisToEarly_User_isLogin(): void
    {
        $client = static::createClient();


        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        self::assertResponseRedirects('/room/dashboard');

        $crawler = $client->request('GET', '/room/dashboard');
        $flash = $crawler->filter('.snackbar .bg-danger')->text();
        self::assertEquals('Der Beitritt ist nur von ' . (clone $room->getStart())->modify('-30min')->format('d.m.Y H:i') . ' bis ' . $room->getEnddate()->format('d.m.Y') . ' ' . $room->getEnddate()->format('H:i') . ' möglich.', $flash);
    }

    public function testRoomisToLate_User_isLogin(): void
    {
        $client = static::createClient();

        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room yesterday']);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $permission = new RoomsUser();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setLobbyModerator(true);
        $manager->persist($permission);
        $manager->flush();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        self::assertResponseRedirects('/room/dashboard');

        $crawler = $client->request('GET', '/room/dashboard');
        $flash = $crawler->filter('.snackbar .bg-danger')->text();
        self::assertEquals('Der Beitritt ist nur von ' . (clone $room->getStart())->modify('-30min')->format('d.m.Y H:i') . ' bis ' . $room->getEnddate()->format('d.m.Y') . ' ' . $room->getEnddate()->format('H:i') . ' möglich.', $flash);
    }
    public function testNoRoom(): void
    {
        $client = static::createClient();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $startService = self::getContainer()->get(StartMeetingService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorroww']);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $urlGen = self::getContainer()->get(UrlGeneratorInterface::class);
        $paramterBag = self::getContainer()->get(ParameterBagInterface::class);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/join/b/-2');
        self::assertResponseRedirects('/room/dashboard');

        $crawler = $client->request('GET', '/room/dashboard');
        $flash = $crawler->filter('.snackbar .bg-danger')->text();
        self::assertEquals('Die Konferenz wurde nicht gefunden. Bitte geben Sie Ihre Zugangsdaten erneut ein.', $flash);
    }
}
