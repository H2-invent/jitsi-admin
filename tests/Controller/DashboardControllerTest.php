<?php


namespace App\Tests\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{

    public function testdashboardUserSuccess()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

    }

    public function testdashboardUserFail()
    {
        $client = static::createClient();
        $userRepository = static::$container->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('testFail@test.com');
      if ($testUser){
          $client->loginUser($testUser);
      }



        $client->request('GET', '/room/dashboard');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testIndex()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    public function testDayDescription()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(1,$crawler->filter('h4:contains("Heute")')->count());
    }
    public function testservername()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $crawler->filter('p:contains("Server: Server with License")')->count());
        self::assertEquals(0, $crawler->filter('p:contains("Server: meet.jit.si2")')->count());
        self::assertEquals(0, $crawler->filter('p:contains("Server: meet.jit.si2")')->count());
        self::assertEquals(2, $crawler->filter('.dropdown-item:contains("Server with License")')->count());
        self::assertEquals(1, $crawler->filter('#settings:contains("Server with License")')->count());
    }
}