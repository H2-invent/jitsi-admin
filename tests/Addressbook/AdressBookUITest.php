<?php

namespace App\Tests\Addressbook;

use App\Entity\Server;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        // With more than one server the phone icon is a server dropdown and each
        // entry starts the ad-hoc call for that server.
        self::assertGreaterThan(
            0,
            $crawler->filter('.adressbookline .icon > a.caretdown.dropdown-toggle[data-mdb-dropdown-init] i.fa-phone-volume')->count()
        );
        self::assertGreaterThan(
            0,
            $crawler->filter('.adressbookline .icon > .dropdown-menu > a.dropdown-item.adhocConfirm')->count()
        );
        self::assertEquals(
            0,
            $crawler->filter('.adressbookline .icon > a.adhocConfirm i.fa-phone-volume')->count()
        );
    }

    public function testPhoneIconDependsOnServerCount(): void
    {
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $viewer = $userRepository->findOneByUsername('test@local.de');
        $contact = $userRepository->findOneByUsername('test2@local.de');

        // The entry template reads app.user; make it resolvable for a direct render.
        $container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($viewer, 'main', $viewer->getRoles())
        );

        $servers = $container->get('doctrine')->getRepository(Server::class)->findAll();
        self::assertGreaterThanOrEqual(2, count($servers));

        $twig = $container->get('twig');
        $render = fn (array $serversForUser): Crawler => new Crawler($twig->render(
            'addressbook/__addressBookEntry.html.twig',
            ['u' => $contact, 'servers' => $serversForUser, 'theme' => false]
        ));

        // No servers: the green phone icon is not rendered at all.
        $none = $render([]);
        self::assertEquals(0, $none->filter('i.fa-phone-volume')->count());
        self::assertEquals(0, $none->filter('a.caretdown')->count());

        // Exactly one server: direct call, no dropdown and no down-arrow button.
        $single = $render([$servers[0]]);
        self::assertEquals(1, $single->filter('a.adhocConfirm i.fa-phone-volume')->count());
        self::assertEquals(0, $single->filter('a.caretdown')->count());
        self::assertEquals(0, $single->filter('.icon a[data-mdb-dropdown-init] i.fa-phone-volume')->count());

        // More than one server: dropdown with one call entry per server.
        $many = $render($servers);
        self::assertEquals(
            1,
            $many->filter('a.caretdown.dropdown-toggle[data-mdb-dropdown-init] i.fa-phone-volume')->count()
        );
        self::assertEquals(
            count($servers),
            $many->filter('.icon > .dropdown-menu > a.dropdown-item.adhocConfirm')->count()
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
                    ['name' => 'Testgruppe', 'user' => ['test2@local.de','test@local3.de']]
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
                        ['name' => 'Testgruppe', 'user' => ['test2@local.de','test@local3.de']]
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
                    ['name' => 'Testgruppe', 'user' => ['test2@local.de','test@local3.de']]
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
                    ['name' => 'Testgruppe', 'user' => ['test2@local.de','test@local3.de']]
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
