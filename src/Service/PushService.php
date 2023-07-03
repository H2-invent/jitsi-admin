<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PushService
{
    private $em;
    private $urlGenerator;
    private $directSend;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, DirectSendService $directSend)
    {
        $this->em = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->directSend = $directSend;
    }

    function generatePushNotification($title, $text, User $user, $url = null, $id = '0x00')
    {
        $topic = 'personal/' . $user->getUid();
        $this->directSend->sendBrowserNotification($topic, $title, $text, $text, $id, 'info');
        $this->directSend->sendRefreshDashboard($topic);
        return true;
    }

    function getNotification(User $user)
    {
        $res = [];
        $notification = $this->em->getRepository(Notification::class)->findBy(['user' => $user], ['createdAt' => 'desc']);

        foreach ($notification as $data) {
            $tmp = [
                'id' => $data->getId(),
                'title' => $data->getTitle(),
                'text' => $data->getText(),
                'url' => $data->getUrl() ? $data->getUrl() : $this->urlGenerator->generate('dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL)];
            $res[] = $tmp;
            $this->em->remove($data);
        }
        $this->em->flush();
        return $res;
    }
}
