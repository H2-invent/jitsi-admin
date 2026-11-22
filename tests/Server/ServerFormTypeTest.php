<?php

namespace App\Tests\Server;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Server;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServerFormTypeTest extends WebTestCase
{
    public function testServerFormRequiresFieldsAndMasksSecret(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $client->loginUser($user);

        $crawler = $client->request('GET', '/server/add');
        self::assertResponseIsSuccessful();

        // The four fields are required on the client side.
        self::assertCount(1, $crawler->filter('input[name="server[url]"][required]'));
        self::assertCount(1, $crawler->filter('input[name="server[appId]"][required]'));
        self::assertCount(1, $crawler->filter('input[name="server[appSecret]"][required]'));
        self::assertCount(1, $crawler->filter('input[name="server[serverName]"][required]'));

        // The secret is masked and has an eye toggle inside the field.
        self::assertCount(1, $crawler->filter('input[name="server[appSecret]"][type="password"]'));
        self::assertCount(1, $crawler->filter('.form-outline > a.toggle-password[data-password-target="server_appSecret"] i.fa-eye'));
    }

    public function testEditServerFormPopulatesMaskedSecret(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $user = $container->get(UserRepository::class)->findOneByUsername('test@local.de');
        $client->loginUser($user);

        $server = $container->get(EntityManagerInterface::class)
            ->getRepository(Server::class)
            ->findOneBy(['serverName' => 'Server with License']);
        self::assertNotNull($server);
        self::assertNotEmpty($server->getAppSecret());

        $crawler = $client->request('GET', '/server/add?id=' . $server->getId());
        self::assertResponseIsSuccessful();

        $input = $crawler->filter('input[name="server[appSecret]"]');
        self::assertCount(1, $input);
        self::assertSame('password', $input->attr('type'));
        self::assertSame($server->getAppSecret(), $input->attr('value'));
    }

    public function testServerFormRejectsMissingRequiredFields(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $client->loginUser($user);

        $client->request('POST', '/server/add', [
            'server' => [
                'url' => '',
                'appId' => '',
                'appSecret' => '',
                'serverName' => '',
                // Fill the other required field so only our fields can fail.
                'jwtModeratorPosition' => 0,
            ],
        ]);

        self::assertFalse($client->getResponse()->isRedirect());
    }
}
