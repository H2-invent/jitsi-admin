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
        $client = static::createClient(['environment' => 'test']);
        $crawler = $client->request('GET', '/');
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $urlGenerator = $this->getContainer()->get(UrlGeneratorInterface::class);
        $client->loginUser($testUser);

        $url = $urlGenerator->generate('search_participant', ['search' => 'test@local2.de']);
        $crawler = $client->request('GET', $url);
        self::assertEquals(
            ['user' => [
                [
                    'name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'nameNoIcon' => 'Test2, 1234, User2, Test2', 'id' => 'test2@local.de', 'uid' => 'kljlsdkjflkjddfgslfjsdlkjsdflkj', 'roles' => ['participant', 'moderator']
                ]
            ], 'group' => []
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $url = $urlGenerator->generate('search_participant', ['search' => 'local2.de']);
        $crawler = $client->request('GET', $url);
        self::assertEquals(
            ['user' => [
                [
                    'name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'nameNoIcon' => 'Test2, 1234, User2, Test2', 'id' => 'test2@local.de', 'uid' => 'kljlsdkjflkjddfgslfjsdlkjsdflkj', 'roles' => ['participant', 'moderator']
                ]
            ], 'group' => []
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $url = $urlGenerator->generate('search_participant', ['search' => 'test']);
        $crawler = $client->request('GET', $url);
        self::assertEquals(
            [
                'user' => [
                    ['name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'nameNoIcon' => 'Test2, 1234, User2, Test2', 'id' => 'test2@local.de', 'uid' => 'kljlsdkjflkjddfgslfjsdlkjsdflkj', 'roles' => ['participant', 'moderator']],
                    ['name' => 'test@local3.de', 'nameNoIcon' => 'test@local3.de', 'id' => 'test@local3.de', 'uid' => 'kjsdfhkjds', 'roles' => ['participant', 'moderator']]
                ],
                'group' => [
                    ['name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de"]
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $url = $urlGenerator->generate('search_participant', ['search' => 'Testgruppe']);
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(
            json_encode(
                [
                    'user' => [
                        ['name' => 'testgruppe', 'id' => 'testgruppe', "nameNoIcon" => "testgruppe", 'roles' => ['participant', 'moderator']]
                    ],
                    'group' => [
                        ['name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de"]
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );

        $url = $urlGenerator->generate('search_participant', ['search' => 'Test']);
        $crawler = $client->request('GET', $url);


        self::assertEquals(
            [
                'user' => [
                    ['name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'nameNoIcon' => 'Test2, 1234, User2, Test2', 'id' => 'test2@local.de', 'uid' => 'kljlsdkjflkjddfgslfjsdlkjsdflkj', 'roles' => ['participant', 'moderator']],
                    ['name' => 'test@local3.de', 'nameNoIcon' => 'test@local3.de', 'id' => 'test@local3.de', 'uid' => 'kjsdfhkjds', 'roles' => ['participant', 'moderator']]
                ],
                'group' => [
                    ['name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de"]
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $url = $urlGenerator->generate('search_participant', ['search' => 'test']);
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(
            [
                'user' => [
                    ['name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'nameNoIcon' => 'Test2, 1234, User2, Test2', 'id' => 'test2@local.de', 'uid' => 'kljlsdkjflkjddfgslfjsdlkjsdflkj', 'roles' => ['participant', 'moderator']],
                    ['name' => 'test@local3.de', 'nameNoIcon' => 'test@local3.de', 'id' => 'test@local3.de', 'uid' => 'kjsdfhkjds', 'roles' => ['participant', 'moderator']]
                ],
                'group' => [
                    ['name' => 'Testgruppe', 'user' => "test2@local.de\ntest@local3.de"]
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $url = $urlGenerator->generate('search_participant', ['search' => '1234']);
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(
            [
                'user' => [
                    ['name' => '<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2', 'nameNoIcon' => 'Test2, 1234, User2, Test2', 'uid' => 'kljlsdkjflkjddfgslfjsdlkjsdflkj', 'id' => 'test2@local.de', 'roles' => ['participant', 'moderator']]
                ],
                'group' => []
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
        $url = $urlGenerator->generate('search_participant', ['search' => 'asdf']);
        $crawler = $client->request('GET', $url);
        $parameterBag = $this->getContainer()->get(ParameterBagInterface::class);

        self::assertEquals(
            json_encode(
                [
                    'user' => [
                        ['name' => 'asdf', 'id' => 'asdf', "nameNoIcon" => "asdf", 'roles' => ['participant', 'moderator']]
                    ],
                    'group' => []
                ]
            ),
            $client->getResponse()->getContent()
        );
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertResponseIsSuccessful();
    }
}
