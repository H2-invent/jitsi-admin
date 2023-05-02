<?php

namespace App\Tests\Star;

use App\Repository\ServerRepository;
use App\Repository\StarRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StarControllerTest extends WebTestCase
{
    public function testSendStar(): void
    {
        $client = static::createClient();
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=3&comment=test123&browser=opera&os=windows');
        self::assertResponseIsSuccessful();
        self::assertEquals(['error' => false], json_decode($client->getResponse()->getContent(), true));
        $starRepo = self::getContainer()->get(StarRepository::class);
        $stars = $starRepo->findAll();
        self::assertEquals(1, sizeof($stars));
        self::assertEquals((new \DateTime())->format('d.m.YTH:i'), $stars[0]->getCreatedAt()->format('d.m.YTH:i'));
        self::assertEquals('windows', $stars[0]->getOs());
        self::assertEquals('opera', $stars[0]->getBrowser());
    }
    public function testSendSomeStarsStar(): void
    {
        $client = static::createClient();
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=5&comment=test123&browser=opera&os=windows');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=4&comment=test123');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=3&comment=test123&os=windows');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=2&comment=test123&browser=chrom');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=1&comment=test123&browser=firefox&os=apple');

        $starRepo = self::getContainer()->get(StarRepository::class);
        $stars = $starRepo->findAll();
        self::assertEquals(5, sizeof($stars));
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/admin/server/' . $server->getId());
        self::assertResponseIsSuccessful();

        $this->assertEquals(
            1,
            $crawler->filter('#rating .label-value:contains("3")')->count()
        );
        $this->assertEquals(
            3,
            $crawler->filter('#rating .star-filled')->count()
        );
        $this->assertEquals(
            2,
            $crawler->filter('#rating .star-empty')->count()
        );
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=5&comment=test123');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=5&comment=test123');
        $crawler = $client->request('GET', '/admin/server/' . $server->getId());

        $this->assertEquals(
            1,
            $crawler->filter('#rating .label-value:contains("3.6")')->count()
        );
        $this->assertEquals(
            3,
            $crawler->filter('#rating .star-filled')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('#rating .star-empty')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('#rating .star-half')->count()
        );
    }

    public function testSendBorderStars(): void
    {
        $client = static::createClient();
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=1&comment=test123');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=1&comment=test123');
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=1&comment=test123');


        $starRepo = self::getContainer()->get(StarRepository::class);
        $stars = $starRepo->findAll();
        self::assertEquals(3, sizeof($stars));
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/admin/server/' . $server->getId());
        self::assertResponseIsSuccessful();

        $this->assertEquals(
            1,
            $crawler->filter('#rating .label-value:contains("1")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('#rating .star-filled')->count()
        );
        $this->assertEquals(
            4,
            $crawler->filter('#rating .star-empty')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('#rating .star-half')->count()
        );

        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=2&comment=test123');
        $crawler = $client->request('GET', '/admin/server/' . $server->getId());
        $this->assertEquals(
            1,
            $crawler->filter('#rating .label-value:contains("1.3")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('#rating .star-filled')->count()
        );
        $this->assertEquals(
            3,
            $crawler->filter('#rating .star-empty')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('#rating .star-half')->count()
        );
        $crawler = $client->request('GET', '/star/submit?server=' . $server->getId() . '&star=1&comment=test123');
        $crawler = $client->request('GET', '/admin/server/' . $server->getId());
        $this->assertEquals(
            1,
            $crawler->filter('#rating .label-value:contains("1.2")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('#rating .star-filled')->count()
        );
        $this->assertEquals(
            4,
            $crawler->filter('#rating .star-empty')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('#rating .star-half')->count()
        );
    }
}
