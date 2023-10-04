<?php

namespace App\Tests\Tag;

use App\Entity\Server;
use App\Entity\Tag;
use App\Repository\ServerRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomTagControllerTest extends WebTestCase
{
    public function testNormalModal(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/new');
        self::assertEquals('KategorieTest Tag EnabledTest Tag Enabled No2Test Tag 0Test Tag 1Test Tag 2Test Tag 3Test Tag 4', $crawler->filter('#form_tag_wrapper')->text());
        self::assertStringContainsString('Server without License', $client->getResponse()->getContent());
        self::assertStringContainsString('Server no JWT', $client->getResponse()->getContent());
        self::assertStringContainsString('Server with License', $client->getResponse()->getContent());
    }
    public function testFakeModalOneTag(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['serverName'=>'Server no JWT']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag1 = $tagRepo->findOneBy(['title'=>'Test Tag Enabled']);
        $server->addTag($tag1);
        $manager->persist($server);
        $manager->flush();

        $crawler = $client->request('GET', '/room/new?serverfake='.$server->getId());
        self::assertEquals('', $crawler->filter('#form_tag_wrapper')->text());
        $dropdownElement = $crawler->filter('#form_tag_wrapper');
        $this->assertEquals(1, $dropdownElement->count());
        $optionElement = $crawler->filter('#form_tag_wrapper option');
        $this->assertEquals(0, $optionElement->count());
        self::assertStringContainsString('Server without License', $client->getResponse()->getContent());
        self::assertStringContainsString('Server no JWT', $client->getResponse()->getContent());
        self::assertStringContainsString('Server with License', $client->getResponse()->getContent());
    }
    public function testFakeModalTwoTags(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['serverName'=>'Server no JWT']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag1 = $tagRepo->findOneBy(['title'=>'Test Tag Enabled']);
        $tag2 = $tagRepo->findOneBy(['title'=>'Test Tag 0']);
        $server->addTag($tag1);
        $server->addTag($tag2);
        $manager->persist($server);
        $manager->flush();

        $crawler = $client->request('GET', '/room/new?serverfake='.$server->getId());
        self::assertEquals('KategorieTest Tag EnabledTest Tag 0', $crawler->filter('#form_tag_wrapper')->text());
        $dropdownElement = $crawler->filter('#form_tag_wrapper');
        $this->assertEquals(1, $dropdownElement->count());
        $optionElement = $crawler->filter('#form_tag_wrapper option');
        $this->assertEquals(2, $optionElement->count());

        $selectedElement = $dropdownElement->filter('option[selected="selected"]');
        $this->assertEquals('Test Tag Enabled', $selectedElement->text());

        self::assertStringContainsString('Server without License', $client->getResponse()->getContent());
        self::assertStringContainsString('Server no JWT', $client->getResponse()->getContent());
        self::assertStringContainsString('Server with License', $client->getResponse()->getContent());
    }

}
