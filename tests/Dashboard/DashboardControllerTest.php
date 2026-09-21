<?php

namespace App\Tests\Dashboard;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testdashboardUserSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertEquals(0, $crawler->filter('.createdFromText')->count());
        self::assertEquals(0, $crawler->filter('.createdByDeputy')->count());
    }

    public function testdashboardUserFail(): void
    {
        $client = static::createClient();
        $userRepository = self::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('testFail@test.com');
        if ($testUser) {
            $client->loginUser($testUser);
        }


        $client->request('GET', '/room/dashboard');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testIndex(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');
        self::assertResponseRedirects('/m');
    }
    public function testDayDescription(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(1, $crawler->filter('h4:contains("Heute")')->count());
    }
    public function testservernameInSettings(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(1, $crawler->filter('#settings:contains("Server with License")')->count());
    }
    public function testservernameinAddhockCall(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(2, $crawler->filter('.dropdown-item:contains("Server with License")')->count());
    }
    public function testservernameinConferenceCard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(69, $crawler->filter('p:contains("Server: Server with License")')->count());
    }
    public function testservernameinnoForeignServerConferenceCard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(0, $crawler->filter('p:contains("Server: meet.jit.si2")')->count());
    }
    public function testlazyLoadFixed(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/room/dashboard/lazy/fixed/0');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSelectorExists('.lazyLoad');
        $this->assertResponseIsSuccessful();

        self::assertEquals(3, $crawler->filter('.card')->count());


        $crawler = $client->request('GET', '/room/dashboard/lazy/fixed/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSelectorNotExists('.lazyLoad');
        $this->assertResponseIsSuccessful();
        self::assertEquals(0, $crawler->filter('.card')->count());
    }
    public function testlazyLoadPast(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);

        $crawler = $client->request('GET', '/room/dashboard/lazy/past/0');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSelectorExists('.lazyLoad');
        $this->assertResponseIsSuccessful();

        self::assertEquals(1, $crawler->filter('.card')->count());


        $crawler = $client->request('GET', '/room/dashboard/lazy/past/1');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        self::assertSelectorNotExists('.lazyLoad');
        $this->assertResponseIsSuccessful();
        self::assertEquals(0, $crawler->filter('.card')->count());
        ;
    }
}
