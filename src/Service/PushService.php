<?php


namespace App\Service;


use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PushService
{
    private $em;
    private $urlGenerator;
    public function __construct(EntityManagerInterface $entityManager,UrlGeneratorInterface $urlGenerator)
    {
        $this->em = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    function generatePushNotification($title, $text,User $user,$url = null){
        $notification = new Notification();
        $notification->setTitle($title);
        $notification->setText($text);
        $notification->setCreatedAt(new \DateTime());
        $notification->setUser($user);
        $notification->setUrl($url);
        $this->em->persist($notification);
        $this->em->flush();
        return $notification;
    }
    function getNotification(User $user){
        $res = array();
        $notification =$this->em->getRepository(Notification::class)->findBy(array('user' => $user), array('createdAt' => 'desc'));

        foreach ($notification as $data) {
            $tmp = array(
                'id'=>$data->getId(),
                'title' => $data->getTitle(),
                'text' => $data->getText(),
                'url' => $data->getUrl()?$data->getUrl():$this->urlGenerator->generate('dashboard', array(), UrlGeneratorInterface::ABSOLUTE_URL));
            $res[] = $tmp;
            $this->em->remove($data);
        }
        $this->em->flush();
        return $res;
    }
}