<?php

namespace App\Tests\Favorites;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FavoriteTest extends WebTestCase
{
    public function testToggleFavorite(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setStart($room->getStart()->modify('-10min'));
        $manager->persist($room);
        $manager->flush();
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $client->followRedirects();
        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));

        $this->assertEquals(
            1,
            $crawler->filter('.favorites:contains("TestMeeting: 1")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.favorites .dropdown-item:contains("In der App")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.favorites .dropdown-item:contains("Starten")')->count()
        );

        $testUser = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', $urlGenerator->generate('dashboard'));
        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("TestMeeting: 1")')->count()
        );
        $testUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("TestMeeting: 1")')->count()
        );
    }

    public function testToggleFavoriteNoUSer(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local4.de']);
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->generate('dashboard')));
        $crawler = $client->request('GET', $urlGenerator->generate('dashboard'));
        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("TestMeeting: 1")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.favorites .dropdown-item:contains("In der App")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.favorites .dropdown-item:contains("Im Browser")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.favorites .badge:contains("Läuft gerade")')->count()
        );
        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        $this->assertTrue($client->getResponse()->isRedirect($urlGenerator->generate('dashboard')));
        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("TestMeeting: 1")')->count()
        );
    }
    public function testToggleRunningRoom(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Running Room']);
        $room->setStart($room->getStart()->modify('-10min'));
        $manager->persist($room);
        $manager->flush();
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $client->followRedirects();
        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));

        $this->assertEquals(
            1,
            $crawler->filter('.favorites:contains("Running Room")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.favorites.successBorder')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.favorites .dropdown-item:contains("In der App")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.favorites .dropdown-item:contains("Starten")')->count()
        );

        $testUser = $userRepository->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', $urlGenerator->generate('dashboard'));
        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("Running Room")')->count()
        );
        $testUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));
        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("Running Room")')->count()
        );
    }
    public function testTogglePastRoom(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $manager->persist($room);
        $manager->flush();
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $client->followRedirects();
        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));

        $this->assertEquals(
            0,
            $crawler->filter('.favorites:contains("Room Yesterday")')->count()
        );
    }
    public function testToggleFixedRoom(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'This is a fixed room']);
        $manager->persist($room);
        $manager->flush();
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $client->followRedirects();
        $crawler = $client->request('GET', $urlGenerator->generate('room_favorite_toogle', ['uid' => $room->getUidReal()]));

        $this->assertEquals(
            1,
            $crawler->filter('.favorites:contains("This is a fixed room")')->count()
        );
    }
}
