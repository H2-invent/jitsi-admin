<?php

namespace App\Tests\Service;

use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\ServerService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServerServiceTest extends KernelTestCase
{
    private function service(): ServerService
    {
        return self::getContainer()->get(ServerService::class);
    }

    public function testAddPermissionReturnsTrueAndSendsEmailToUser(): void
    {
        self::bootKernel();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $result = $this->service()->addPermission($server, $user);

        self::assertTrue($result);
        self::assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
    }

    public function testMakeSlugSlugifiesInputWhenNoCollision(): void
    {
        self::bootKernel();
        $input = 'Slug ' . uniqid('', false) . ' Test';
        $expected = UtilsHelper::slugify($input);

        self::assertNull(self::getContainer()->get(ServerRepository::class)->findOneBy(['slug' => $expected]));

        self::assertSame($expected, $this->service()->makeSlug($input));
    }

    public function testMakeSlugAppendsCounterWhileSlugIsTaken(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $input = 'Slug Collision Test';
        $base = UtilsHelper::slugify($input);

        self::assertSame('slug_collision_test', $base);
        self::assertNull(self::getContainer()->get(ServerRepository::class)->findOneBy(['slug' => $base]));

        $this->persistServer($em, $base, 'slug-collision.test');
        self::assertSame($base . '-1', $this->service()->makeSlug($input));

        $this->persistServer($em, $base . '-1', 'slug-collision-1.test');
        self::assertSame($base . '-2', $this->service()->makeSlug($input));
    }

    private function persistServer(EntityManagerInterface $em, string $slug, string $url): void
    {
        $server = new Server();
        $server->setUrl($url);
        $server->setSlug($slug);
        $server->setJwtModeratorPosition(0);
        $server->setServerName('Slug Test Server');

        $em->persist($server);
        $em->flush();
    }
}
