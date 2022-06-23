<?php

namespace App\Tests\Addressbook;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdressBookUITest extends WebTestCase
{
    public function testAdressbookUI(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
        self::assertEquals(1, $crawler->filter('#profile:contains("Testgruppe (2)")')->count());
        $this->assertEquals(
            1,
            $crawler->filter('.breakWord:contains("Test2, 1234, User2, Test2")')->count()
        );
        $this->assertEquals(
            3,
            $crawler->filter('.breakWord:contains("")')->count()
        );
    }

    public function testSearchUser(): void
    {
        $client = static::createClient(array('environment'=>'test'));
        $crawler = $client->request('GET', '/');
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $client->loginUser($testUser);

        $url = $urlGenerator->generate('search_participant', array('search' => 'test@local2.de'));
        $crawler = $client->request('GET', $url);
        self::assertEquals(array('user' => array(
            array(
                'name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'id' => 'test2@local.de'
            )
        ), 'group' => array()
        )
        , json_decode($client->getResponse()->getContent(),true));
        $url = $urlGenerator->generate('search_participant', array('search' => 'local2.de'));
        $crawler = $client->request('GET', $url);
        self::assertEquals(array('user' => array(
            array(
                'name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'id' => 'test2@local.de'
            )
        ), 'group' => array()
        )
        , json_decode($client->getResponse()->getContent(),true));
        $url = $urlGenerator->generate('search_participant', array('search' => 'test'));
        $crawler = $client->request('GET', $url);
        self::assertEquals(
            array(
                'user' => array(
                    array('name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'id' => 'test2@local.de'),
                    array('name' => '', 'id' => 'test@local3.de')
                ),
                'group' => array(
                        array('name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de")
                )
            )
        , json_decode($client->getResponse()->getContent(), true));
        $url = $urlGenerator->generate('search_participant', array('search' => 'Testgruppe'));
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(json_encode(
            array(
                'user' => array(
                    array('name' => 'testgruppe', 'id' => 'testgruppe')
                ),
                'group' => array(
                    array('name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de")
                )
            )
        ), $client->getResponse()->getContent());

        $url = $urlGenerator->generate('search_participant', array('search' => 'Test'));
        $crawler = $client->request('GET', $url);


        self::assertEquals(
            array(
                'user' => array(
                    array('name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'id' => 'test2@local.de'),
                     array('name' => '', 'id' => 'test@local3.de')
                ),
                'group' => array(
                    array('name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de")
                )
            )
            , json_decode($client->getResponse()->getContent(),true));
        $url = $urlGenerator->generate('search_participant', array('search' => 'test'));
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(
            array(
                'user' => array(
                    array('name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'id' => 'test2@local.de'),
                     array('name' => '', 'id' => 'test@local3.de')
                ),
                'group' => array(
                    array('name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de")
                )
            )
        , json_decode($client->getResponse()->getContent(),true));
        $url = $urlGenerator->generate('search_participant', array('search' => '1234'));
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(
            array(
                'user' => array(
                    array('name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'id' => 'test2@local.de')
                ),
                'group' => array(

                )
            )
        , json_decode($client->getResponse()->getContent(),true));
        $url = $urlGenerator->generate('search_participant', array('search' => 'asdf'));
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(json_encode(
            array(
                'user' => array(
                    array('name' => 'asdf', 'id' => 'asdf')
                ),
                'group' => array(

                )
            )
        ), $client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();

    }

}
