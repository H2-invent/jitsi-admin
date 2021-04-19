<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Repository\RoomsUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SubcriptionService
{
    private $em;
    private $twig;
    private $translator;
    private $notifier;
    private $userService;
    public function __construct(UserService $userService, NotificationService  $notificationService,EntityManagerInterface $entityManager, Environment $environment, TranslatorInterface $translator)
    {
        $this->em = $entityManager;
        $this->twig = $environment;
        $this->translator = $translator;
        $this->notifier = $notificationService;
        $this->userService = $userService;
    }

    public function subscripe($email, Rooms $rooms,$moderator = false)
    {
        $res = array('error'=>true);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->em->getRepository(User::class)->findOneBy(array('email' => $email));
            //create a new User from the email entered
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $this->em->persist($user);
                $this->em->flush();
            }
            $subscriber = $this->em->getRepository(Subscriber::class)->findOneBy(array('room' => $rooms, 'user' => $user));
            if ($subscriber) {
                $res['text'] = $this->translator->trans('Sie haben sich bereits angemeldet. Bite bestätigen sie noch ihre Anmeldung durch klick auf den Link in der Email.');
                $res['color'] = 'danger';
            } elseif (in_array($rooms, $user->getRooms()->toArray())) {
                $res['text']  = $this->translator->trans('Sie haben sich bereits angemeldet.');
                $res['color']  = 'danger';
            } else {
                $subscriber = new Subscriber();
                $subscriber->setUser($user);
                $subscriber->setRoom($rooms);
                $subscriber->setUid(md5(uniqid()));
                $this->em->persist($subscriber);
                $this->em->flush();
                $res['text'] = $this->translator->trans('Vielen Dank für die Anmeldung. Bitte bestätigen Sie Ihre Emailadresse in der Email, die wir ihnen zugeschickt haben.');
                $res['color'] = 'success';
                $res['error']= false;
                if($moderator == true){
                    $usersRoom = new RoomsUser();
                    $usersRoom->setRoom($rooms);
                    $usersRoom->setUser($user);
                    $usersRoom->setModerator(true);
                    $usersRoom->setPrivateMessage(true);
                    $usersRoom->setShareDisplay(true);
                    $this->em->persist($usersRoom);
                    $this->em->flush();
                }
                $this->notifier->sendNotification(
                    $this->twig->render('email/subscriptionToRoom.html.twig',array('room'=>$rooms,'subsription'=>$subscriber)),
                    $this->translator->trans('Bestätigung ihrer Anmeldung zur Konferenz: {name}',array('{name}'=>$rooms->getName())),
                    $user,
                    $rooms->getServer()
                );
            }
        } else {
            $res['text']  = $this->translator->trans('Ungültige Email. Bitte überprüfen Sie ihre Emailadresse.');
            $res['color']  = 'danger';
        }
        return $res;
    }
    public function acceptSub(?Subscriber $subscriber){
        $res['message'] =$this->translator->trans('Danke für die Anmeldung. ');
        $res['title'] =$this->translator->trans('Erfolgreich bestätigt');
        if($subscriber){
            if($subscriber->getRoom()->getMaxParticipants()!= null && sizeof($subscriber->getRoom()->getUser()) >= $subscriber->getRoom()->getMaxParticipants()){
                $res['message'] =$this->translator->trans('Die maximale Teilnehmeranzahl ist bereits erreicht.');
                $res['title'] =$this->translator->trans('Fehler');
                return $res;
            }
            try {
                $subscriber->getUser()->addRoom($subscriber->getRoom());
                $user = $subscriber->getUser();
                $room = $subscriber->getRoom();

                $user->addRoom($subscriber->getRoom());
                $this->em->persist($user);
                $this->em->remove($subscriber);
                $this->em->flush();
                $this->userService->addUser($user,$room);
            }catch (\Exception $exception){
                $res['message'] =$this->translator->trans('Fehler, Bitte klicken Sie den link erneut an.');
                $res['title'] =$this->translator->trans('Fehler');

            }
        }else{
            $res['message'] =$this->translator->trans('Dieser Link ist ungültig. Wahrscheinlich wurde er bereits bestätigt.');
            $res['title'] =$this->translator->trans('Fehler');

        }
        return $res;
    }
}