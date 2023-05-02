<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Entity\Waitinglist;
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
    private $userCreationService;
    public function __construct(UserService $userService, NotificationService $notificationService, EntityManagerInterface $entityManager, Environment $environment, TranslatorInterface $translator, UserCreatorService $userCreationService)
    {
        $this->em = $entityManager;
        $this->twig = $environment;
        $this->translator = $translator;
        $this->notifier = $notificationService;
        $this->userService = $userService;
        $this->userCreationService = $userCreationService;
    }

    /**
     * @param $userData
     * @param Rooms $rooms
     * @param false $moderator
     * @return array|bool[]
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * Creates a subscriber. A subscriber is not a participent. a subscriber need to double opt in
     * This functions sends a mail to the subscriper with the double opt in link.
     * This function checks if the room is full and if so then it will reject or if the waiting list is active then the user can register
     */
    public function subscripe($userData, Rooms $rooms, $moderator = false)
    {
        $res = ['error' => true];
        if ($rooms->getMaxParticipants() && (sizeof($rooms->getUser()->toArray()) >= $rooms->getMaxParticipants()) && $rooms->getWaitinglist() != true) {
            $res['text'] = $this->translator->trans('Die maximale Teilnehmeranzahl ist bereits erreicht.');
            $res['color'] = 'danger';
            return $res;
        }
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $res['text'] = $this->translator->trans('Ungültige Email. Bitte überprüfen Sie ihre Emailadresse.');
            $res['color'] = 'danger';
            return $res;
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
        //create a new User from the email entered
        if (!$user) {
            $user = $this->userCreationService->createUser($userData['email'], $userData['email'], $userData['firstName'], $userData['lastName']);
        }

        $subscriber = $this->em->getRepository(Subscriber::class)->findOneBy(['room' => $rooms, 'user' => $user]);

        if ($subscriber) {
            $res['text'] = $this->translator->trans('Sie haben sich bereits angemeldet. Bite bestätigen sie noch ihre Anmeldung durch klick auf den Link in der Email.');
            $res['color'] = 'danger';
        } elseif (in_array($rooms, $user->getRooms()->toArray())) {
            $res['text'] = $this->translator->trans('Sie haben sich bereits angemeldet.');
            $res['color'] = 'danger';
        } else {
            $res = $this->createNewSubscriber($user, $rooms);
            $subscriber = $res['sub'];
            if ($moderator == true) {
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
                $this->twig->render('email/subscriptionToRoom.html.twig', ['room' => $rooms, 'subsription' => $subscriber]),
                $this->translator->trans('[Videokonferenz] Bestätigung ihrer Anmeldung zur Konferenz: {name}', ['{name}' => $rooms->getName()]),
                $user,
                $rooms->getServer(),
                $rooms
            );
        }

        return $res;
    }

    /**
     * @param Subscriber|null $subscriber
     * @return array
     * checks the subsriber an creates a roomUser connection or a waitinglist Element
     */
    public function acceptSub(?Subscriber $subscriber)
    {
        $res['message'] = $this->translator->trans('Danke für die Anmeldung. ');
        $res['title'] = $this->translator->trans('Erfolgreich bestätigt');
        if (!$subscriber) {
            $res['message'] = $this->translator->trans('Dieser Link ist ungültig. Wahrscheinlich wurde er bereits bestätigt.');
            $res['title'] = $this->translator->trans('Fehler');
            return $res;
        }


        if ($subscriber->getRoom()->getMaxParticipants() != null && sizeof($subscriber->getRoom()->getUser()) >= $subscriber->getRoom()->getMaxParticipants() && $subscriber->getRoom()->getWaitinglist() != true) {
            $res['message'] = $this->translator->trans('Die maximale Teilnehmeranzahl ist bereits erreicht.');
            $res['title'] = $this->translator->trans('Fehler');
            return $res;
        }

        try {
            if ($subscriber->getRoom()->getMaxParticipants() != null && sizeof($subscriber->getRoom()->getUser()) >= $subscriber->getRoom()->getMaxParticipants()) {
                $this->createNewWaitinglist($subscriber->getUser(), $subscriber->getRoom());
                $this->em->remove($subscriber);
                $this->em->flush();
            } else {
                $this->createUserRoom($subscriber->getUser(), $subscriber->getRoom());
                $this->em->remove($subscriber);
                $this->em->flush();
            }
        } catch (\Exception $exception) {
            $res['message'] = $this->translator->trans('Fehler, Bitte klicken Sie den link erneut an.');
            $res['title'] = $this->translator->trans('Fehler');
        }

        return $res;
    }

    /**
     * @param User $user
     * @param Rooms $rooms
     * @return array
     * creates a new subscriber element
     */
    function createNewSubscriber(User $user, Rooms $rooms)
    {
        $subscriber = new Subscriber();
        $subscriber->setUser($user)->setRoom($rooms)->setUid(md5(uniqid()));
        $this->em->persist($subscriber);
        $this->em->flush();
        $res['text'] = $this->translator->trans('Vielen Dank für die Anmeldung. Bitte bestätigen Sie Ihre Emailadresse in der Email, die wir ihnen zugeschickt haben.');
        $res['color'] = 'success';
        $res['error'] = false;
        $res['sub'] = $subscriber;
        return $res;
    }

    /**
     * @param User $user
     * @param Rooms $rooms
     * @return array
     * creates a new Waiinglist element and sends the email with the waiting list to the subscriber
     */
    function createNewWaitinglist(User $user, Rooms $rooms)
    {
        $waitingList = new Waitinglist();
        $waitingList->setUser($user)->setRoom($rooms)->setCreatedAt(new \DateTime());
        $this->em->persist($waitingList);
        $this->em->flush();
        $res['text'] = $this->translator->trans('Vielen Dank für die Anmeldung. Bitte bestätigen Sie Ihre Emailadresse in der Email, die wir ihnen zugeschickt haben.');
        $res['color'] = 'success';
        $res['error'] = false;
        $this->userService->addWaitinglist($user, $rooms);
        return $res;
    }

    /**
     * @param User $user
     * @param Rooms $rooms
     * creates a new roomUser element and sends the email with the room infos  to the subscriber
     */
    function createUserRoom(User $user, Rooms $rooms)
    {
        $user->addRoom($rooms);
        $this->em->persist($user);
        $this->em->flush();
        $this->userService->addUser($user, $rooms);
    }
}
