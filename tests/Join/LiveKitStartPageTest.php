<?php

namespace App\Tests\Join;

use App\Entity\Server;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LiveKitStartPageTest extends WebTestCase
{
    /**
     * A LiveKit server may be configured without JWT credentials. The start page
     * must still render instead of passing a null payload into JWT::encode().
     */
    public function testLiveKitStartPageWithoutCredentials(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $user = $container->get(UserRepository::class)->findOneByUsername('test@local.de');
        $client->loginUser($user);

        $room = $container->get(RoomsRepository::class)->findOneBy(['name' => 'Room with Start and no Participants list']);
        self::assertNotNull($room);

        $server = new Server();
        $server->setUrl('livekit-rtc.dev12.h2-invent.com')
            ->setServerName('LiveKit Test')
            ->setSlug('livekitTest')
            ->setLiveKitServer(true)
            ->setJwtModeratorPosition(0)
            ->setPrivacyPolicy('https://privacy.dev')
            ->setAppId(null)
            ->setAppSecret(null);
        $em->persist($server);

        $room->setServer($server);
        $room->setLobby(false);
        $em->persist($room);
        $em->flush();

        // Avoid a real HTTP fetch of the middleware public key.
        $publicKey = file_get_contents($container->get(ParameterBagInterface::class)->get('kernel.project_dir') . '/testJwt/public.pem');
        $cache = $container->get('cache.app');
        $item = $cache->getItem('livekit_public_key_' . $server->getId());
        $item->set($publicKey);
        $item->expiresAfter(3600);
        $cache->save($item);

        $client->request('GET', '/room/join/b/' . $room->getId());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('/meetling/room/', $client->getResponse()->getContent());
    }
}
