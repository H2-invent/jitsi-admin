<?php

namespace App\Tests\Addressbook;

use App\Entity\Server;
use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertStringContainsString;

class AdhocControllerConfirmationTest extends WebTestCase
{
    public function testcreateAdhocMeetingConfirmationWindowwithNoTag(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findAll();
        foreach ($tag as $data) {
            $em->remove($data);
        }

        $em->flush();

        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room) - 1];
        self::assertResponseIsSuccessful();
        assertStringContainsString('/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId(), $client->getResponse()->getContent());
        self::assertEquals(
            1,
            $crawler->filter('option')->count()
        );
        self::assertEquals(
            1,
            $crawler->filter('.d-none')->count()
        );
        self::assertEquals(
            1,
            $crawler->filter('option:contains("")')->count()
        );
    }

    public function testcreateAdhocMeetingConfirmationWindowwithOneTag(): void
    {
        $client = static::createClient();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tagEnable = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        $tag = $tagRepo->findAll();
        foreach ($tag as $data) {
            if ($data !== $tagEnable) {
                $em->remove($data);
            }
        }

        $em->flush();

        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room) - 1];
        self::assertResponseIsSuccessful();

        assertStringContainsString('/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId() . '/' . $tagEnable->getId(), $client->getResponse()->getContent());
        self::assertSelectorTextContains('option', 'Test Tag Enabled');
        self::assertEquals(
            1,
            $crawler->filter('option')->count()
        );
        self::assertEquals(
            1,
            $crawler->filter('.d-none')->count()
        );
        self::assertEquals(
            1,
            $crawler->filter('option:contains("")')->count()
        );
    }

    public function testcreateAdhocMeetingConfirmationWindowwithmanyTag(): void
    {
        $client = static::createClient();


        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room) - 1];
        self::assertResponseIsSuccessful();
        self::assertEquals(
            7,
            $crawler->filter('option')->count()
        );
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tagEnable = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        $tag = $user->getServers()[0]->getTag();
        foreach ($tag as $data) {
            assertStringContainsString('/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId() . '/' . $data->getId(), $client->getResponse()->getContent());

        }
    }
    public function testcreateAdhocMeetingConfirmationWindowwithnoTags(): void
    {
        $client = static::createClient();


        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);


        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[1]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        self::assertResponseIsSuccessful();
        self::assertEquals(
            1,
            $crawler->filter('option')->count()
        );
    }

}
