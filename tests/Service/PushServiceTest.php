<?php

namespace App\Tests\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\PushService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PushServiceTest extends KernelTestCase
{
    public function testGeneratePushNotificationSendsBrowserNotificationAndRefresh(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $user = new User();
        $user->setUid('user-uid-123');

        $directSend = $this->createMock(DirectSendService::class);
        $directSend->expects($this->once())
            ->method('sendBrowserNotification')
            ->with('personal/user-uid-123', 'Title', 'Text', 'Text', '0x2A', 'info');
        $directSend->expects($this->once())
            ->method('sendRefreshDashboard')
            ->with('personal/user-uid-123');

        $service = new PushService(
            $container->get(EntityManagerInterface::class),
            $container->get(UrlGeneratorInterface::class),
            $directSend
        );

        self::assertTrue($service->generatePushNotification('Title', 'Text', $user, null, '0x2A'));
    }

    public function testGetNotificationReturnsAndDeletesNotifications(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);

        $notification = new Notification();
        $notification->setTitle('Notify title')
            ->setText('Notify text')
            ->setUser($user)
            ->setCreatedAt(new \DateTime());
        $em->persist($notification);
        $em->flush();
        $notificationId = $notification->getId();

        $service = new PushService(
            $em,
            $container->get(UrlGeneratorInterface::class),
            $this->createMock(DirectSendService::class)
        );

        $result = $service->getNotification($user);

        self::assertCount(1, $result);
        self::assertSame('Notify title', $result[0]['title']);
        self::assertSame('Notify text', $result[0]['text']);
        self::assertSame(
            $container->get(UrlGeneratorInterface::class)->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL),
            $result[0]['url']
        );
        self::assertNull($container->get(NotificationRepository::class)->find($notificationId));
    }

    public function testGetNotificationUsesStoredUrl(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);

        $notification = new Notification();
        $notification->setTitle('T')
            ->setText('X')
            ->setUser($user)
            ->setUrl('https://example.org/custom')
            ->setCreatedAt(new \DateTime());
        $em->persist($notification);
        $em->flush();

        $service = new PushService(
            $em,
            $container->get(UrlGeneratorInterface::class),
            $this->createMock(DirectSendService::class)
        );

        $result = $service->getNotification($user);

        self::assertCount(1, $result);
        self::assertSame('https://example.org/custom', $result[0]['url']);
        self::assertCount(0, $container->get(NotificationRepository::class)->findBy(['user' => $user]));
    }
}
