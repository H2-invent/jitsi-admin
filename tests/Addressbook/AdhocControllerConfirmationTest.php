<?php

namespace App\Tests\Addressbook;

use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertStringContainsString;

class AdhocControllerConfirmationTest extends WebTestCase
{
    public function testcreateAdhocMeetingConfirmationWindowwithNoTag(): void
    {
        $client = static::createClient();


        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findAll();
        foreach ($tag as $data) {
            $em->remove($data);
        }

        $em->flush();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room) - 1];
        self::assertResponseIsSuccessful();
        assertStringContainsString('/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId(), $client->getResponse()->getContent());
        self::assertEquals(1,
            $crawler->filter('option')->count());
        self::assertEquals(1,
            $crawler->filter('.d-none')->count());
        self::assertEquals(1,
            $crawler->filter('option:contains("")')->count());
    }

    public function testcreateAdhocMeetingConfirmationWindowwithOneTag(): void
    {
        $client = static::createClient();


        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tagEnable = $tagRepo->findOneBy(array('title' => 'Test Tag Enabled'));
        $tag = $tagRepo->findAll();
        foreach ($tag as $data) {
            if ($data !== $tagEnable) {
                $em->remove($data);
            }
        }

        $em->flush();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room) - 1];
        self::assertResponseIsSuccessful();

        assertStringContainsString('/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId().'/'.$tagEnable->getId(), $client->getResponse()->getContent());
        self::assertSelectorTextContains('option','Test Tag Enabled');
        self::assertEquals(1,
            $crawler->filter('option')->count());
        self::assertEquals(1,
            $crawler->filter('.d-none')->count());
        self::assertEquals(1,
            $crawler->filter('option:contains("")')->count());

    }

    public function testcreateAdhocMeetingConfirmationWindowwithmanyTag(): void
    {
        $client = static::createClient();


        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/adhoc/confirmation/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll();
        $room = $room[sizeof($room) - 1];
        self::assertResponseIsSuccessful();
        self::assertEquals(6,
            $crawler->filter('option')->count());
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tagEnable = $tagRepo->findOneBy(array('title' => 'Test Tag Enabled'));
        $tag = $tagRepo->findBy(array('disabled'=>false));
        foreach ($tag as $data) {
            assertStringContainsString('/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId().'/'.$data->getId(), $client->getResponse()->getContent());
            self::assertEquals(1,
                $crawler->filter('option:contains("'.$data->getTitle().'")')->count());
        }
    }
}
