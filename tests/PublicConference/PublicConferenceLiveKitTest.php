<?php

namespace App\Tests\PublicConference;

use App\Controller\PublicConferenceController;
use App\Entity\Server;
use App\Service\PublicConference\PublicConferenceService;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PublicConferenceLiveKitTest extends WebTestCase
{
    /**
     * Anonymous participants of a LiveKit room are asked for name and company by the
     * middleware itself, so the JWT must not carry a name. It carries the privacy
     * policy link instead, which the middleware shows next to that form.
     */
    public function testAnonymousPublicConferenceOmitsNameAndAddsPrivacyPolicy(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $server = new Server();
        $server->setUrl('livekit-rtc.example.com')
            ->setServerName('LiveKit Test')
            ->setSlug('livekitTest')
            ->setLiveKitServer(true)
            ->setJwtModeratorPosition(0)
            ->setAppId('testID')
            ->setAppSecret('testSecrettestSecrettestSecrettestSecret');
        $em->persist($server);
        $em->flush();

        // Avoid a real HTTP fetch of the middleware public key.
        $projectDir = $container->get(ParameterBagInterface::class)->get('kernel.project_dir');
        $publicKey = file_get_contents($projectDir . '/testJwt/public.pem');
        $cache = $container->get('cache.app');
        $cacheItem = $cache->getItem('livekit_public_key_' . $server->getId());
        $cacheItem->set($publicKey);
        $cacheItem->expiresAfter(3600);
        $cache->save($cacheItem);

        $publicConferenceController = $container->get(PublicConferenceController::class);
        $publicConferenceController->setServer($server);

        $room = $container->get(PublicConferenceService::class)->createNewRoomFromName('livekitRoom', $server);
        $crawler = $client->request('GET', '/m/' . $room->getName());

        self::assertResponseIsSuccessful();
        self::assertSame(
            1,
            $crawler->filterXPath('//script[contains(., "/meetling/room/")]')->count(),
            'the conference page links to the LiveKit middleware'
        );

        $livekitScript = '';
        foreach ($crawler->filterXPath('//script[contains(., "/meetling/room/")]') as $scriptNode) {
            $livekitScript .= $scriptNode->textContent;
        }

        preg_match('#/meetling/room/[^?]+\?jwt=([^\'&"]+)#', $livekitScript, $matches);
        self::assertNotEmpty($matches[1] ?? null, 'the middleware URL contains the JWT');

        $decoded = json_decode(json_encode(JWT::decode($matches[1], new Key($server->getAppSecret(), 'HS256'))), true);

        self::assertArrayNotHasKey('name', $decoded['context']['user']);
        self::assertSame(
            (string) $container->getParameter('dataPrivacy'),
            $decoded['context']['user']['privacyPolicy']
        );
    }
}
