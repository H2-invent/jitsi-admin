<?php

namespace App\Tests\Repeater;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RepeaterControllerTest extends WebTestCase
{
    public function testRepeaterControllerCreateEditDelete(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $crawler = $client->request('GET', '/room/repeater/new?room=' . $room->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Serientermin festlegen');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['repeater[repeatType]'] = 0;
        $form['repeater[repeaterDays]'] = 1;
        $form['repeater[repetation]'] = 10;
        $client->submit($form);
        self::assertTrue($client->getResponse()->isRedirect('/room/dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Sie haben erfolgreich einen Serientermin erstellt.');


        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(11, sizeof($rooms));
        $start = $room->getStart();
        $start->setTime($start->format('H'), $start->format('i'), 0);

        foreach ($rooms as $data) {


            if ($data->getRepeater()) {

                self::assertEquals($start, $data->getStart());
                $start->modify('+1day');
            } else {
                self::assertEquals($data->getStart(), $data->getRepeaterProtoype()->getStartDate());
            }
        }
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $allCards = $this->loadAllFutureTabCards($client);
        self::assertEquals(10, $allCards->filter('.h5-responsive:contains("TestMeeting: 0")')->count());

        //Edit the prototype to change all Rooms
        $crawler = $client->request('GET', '/room/repeater/edit/room?id=' . $rooms[5]->getId() . '&type=all');


        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[start]'] = '2022-04-10T12:00:00';
        $client->submit($form);

        self::assertEquals('{"error":false,"redirectUrl":"\/room\/dashboard?snack=Sie%20haben%20erfolgreich%20einen%20Serientermin%20bearbeitet.\u0026color=success"}', $client->getResponse()->getContent());

        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(11, sizeof($rooms));
        $start = new \DateTime('2022-04-10T12:00:00');
        $start->setTime($start->format('H'), $start->format('i'), 0);
        foreach ($rooms as $data) {
            if ($data->getRepeater()) {
                self::assertEquals($start, $data->getStart());
                $start->modify('+1day');
            } else {
                self::assertEquals($data->getStart(), $data->getRepeaterProtoype()->getStartDate());
            }
        }
        //edit the repeateer Type
        $crawler = $client->request('GET', '/room/repeater/edit/repeat?repeat=' . $rooms[5]->getRepeater()->getId());


        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['repeater[repetation]'] = 3;
        $form['repeater[repeaterDays]'] = 3;
        $client->submit($form);


        self::assertTrue($client->getResponse()->isRedirect('/room/dashboard'));

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar .bg-success')->text();
        self::assertEquals($flashMessage, 'Sie haben erfolgreich einen Serientermin bearbeitet.');

        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(4, sizeof($rooms));
        $start = new \DateTime('2022-04-10T12:00:00');
        foreach ($rooms as $data) {
            if ($data->getRepeater()) {
                self::assertEquals($start, $data->getStart());
                $start->modify('+3days');
            } else {
                self::assertEquals($data->getStart(), $data->getRepeaterProtoype()->getStartDate());
            }
        }

        $crawler = $client->request('GET', '/room/repeater/remove?repeat=' . $rooms[2]->getRepeater()->getId());
        $rooms = $roomRepo->findBy(['name' => 'TestMeeting: 0'],['start'=>'ASC']);
        self::assertEquals(4, sizeof($rooms));
        foreach ($rooms as $data) {
            self::assertEquals(0, sizeof($data->getUser()));
        }
    }

    /**
     * Renders the Future Conferences tab and follows every lazy-load target until the list
     * is exhausted, returning a Crawler over all loaded room cards.
     *
     * The dashboard now only renders the first page per tab; the remaining conferences are
     * loaded lazily on scroll. This helper reproduces that behaviour so assertions can count
     * cards across all pages instead of only the initial one.
     */
    private function loadAllFutureTabCards(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): \Symfony\Component\DomCrawler\Crawler
    {
        $crawler = $client->request('GET', '/room/dashboard');
        $html = $crawler->filter('#ex1-tabs-1')->html();

        $visited = [];
        $urls = [];
        $crawler->filter('#ex1-tabs-1 .lazyLoad')->each(function ($node) use (&$urls) {
            $target = $node->attr('data-target');
            if ($target) {
                $urls[] = $target;
            }
        });

        while (!empty($urls)) {
            $url = array_shift($urls);
            if (in_array($url, $visited, true)) {
                continue;
            }
            $visited[] = $url;

            $crawler = $client->request('GET', $url);
            if ($client->getResponse()->getStatusCode() === 200) {
                $html .= $crawler->html();
                $crawler->filter('.lazyLoad')->each(function ($node) use (&$urls) {
                    $target = $node->attr('data-target');
                    if ($target) {
                        $urls[] = $target;
                    }
                });
            }
        }

        return new \Symfony\Component\DomCrawler\Crawler($html);
    }
}
