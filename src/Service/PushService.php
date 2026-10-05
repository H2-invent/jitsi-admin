<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PushService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UrlGeneratorInterface $urlGenerator, private readonly DirectSendService $directSend)
    {
    }

    public function generatePushNotification(string $title, string $text, User $user, ?string $url = null, string $id = '0x00'): bool
    {
        $topic = 'personal/' . $user->getUid();
        $this->directSend->sendBrowserNotification($topic, $title, $text, $text, $id, 'info');
        $this->directSend->sendRefreshDashboard($topic);
        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNotification(User $user): array
    {
        $res = [];
        $notification = $this->em->getRepository(Notification::class)->findBy(['user' => $user], ['createdAt' => 'desc']);

        foreach ($notification as $data) {
            $tmp = [
                'id' => $data->getId(),
                'title' => $data->getTitle(),
                'text' => $data->getText(),
                'url' => $data->getUrl() ?: $this->urlGenerator->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            $res[] = $tmp;
            $this->em->remove($data);
        }
        $this->em->flush();
        return $res;
    }
}
