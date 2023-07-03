<?php

namespace App\Service;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use App\Service\caller\CallerPrepareService;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Guides\RestructuredText\Directives\Replace;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RepeaterService
{
    private $em;
    private $mailer;

    private $icalService;

    private $translator;
    private $twig;
    private $callerUserService;
    private $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday'
    ];
    private $number = [
        0 => 'First',
        1 => 'Second',
        2 => 'Third',
        3 => 'Fourth',
        4 => 'Fifth',
        5 => 'Last',
    ];
    private $months = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July ',
        'August',
        'September',
        'October',
        'November',
        'December',
    ];

    public function __construct(
        CallerPrepareService   $callerPrepareService,
        IcalService            $icalService,
        Environment            $environment,
        TranslatorInterface    $translator,
        MailerService          $mailerService,
        EntityManagerInterface $entityManager,
    )
    {
        $this->em = $entityManager;
        $this->mailer = $mailerService;
        $this->translator = $translator;
        $this->twig = $environment;
        $this->icalService = $icalService;
        $this->callerUserService = $callerPrepareService;
    }

    /**
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createNewRepeater(Repeat $repeat): Repeat
    {

        $userAttribute = $repeat->getPrototyp()->getUserAttributes()->toArray();
        switch ($repeat->getRepeatType()) {
            case 0:
                $repeat = $this->createDaily($repeat);
                break;
            case 1:
                $repeat = $this->createWeekly($repeat);
                break;
            case 2:
                $repeat = $this->createMontly($repeat);
                break;
            case 3:
                $repeat = $this->createMontlyRelative($repeat);
                break;
            case 4:
                $repeat = $this->createYearly($repeat);
                break;
            case 5:
                $repeat = $this->createYearlyRelative($repeat);
                break;
            default:
                break;
        }
        foreach ($userAttribute as $data) {
            $repeat->getPrototyp()->addUserAttribute($data);
        }

        return $repeat;
    }


    /**
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createDaily(Repeat $repeat): Repeat
    {
        //hier bauen wir alle X tage einen neuenRoom
        $start = $repeat->getStartDate();
        $prototype = $this->em->getRepository(Rooms::class)->find($repeat->getPrototyp()->getId());
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeaterDays() . ' days');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createWeekly(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeaterWeeks() . ' weeks');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createMontly(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeatMontly() . ' months');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createMontlyRelative(Repeat $repeat): Repeat
    {

        $s = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $s->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        $start = clone $s;
        $startTmp = clone $start;
        $startTmp->modify('first day of this month');
        $text = $this->number[$repeat->getRepatMonthRelativNumber()] . ' ' . $this->days[$repeat->getRepatMonthRelativWeekday()] . ' of this month';
        $startTmp->modify($text);
        $startTmp->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        $sollCounter = $repeat->getRepetation();
        if ($startTmp >= $start) {
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start->modify('first day of this month');
            $start->modify('+' . ($repeat->getRepeatMonthlyRelativeHowOften()) . ' months');
            $sollCounter--;
        } else {
            $start->modify('first day of next Month');
        }

        for ($i = 0; $i < $sollCounter; $i++) {
            $start->modify($text);
            $startTmp = clone $start;
            $startTmp->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $repeat->addRoom($room);
            $start->modify('first day of this month');
            $start->modify('+' . ($repeat->getRepeatMonthlyRelativeHowOften()) . ' months');
        }

        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createYearly(Repeat $repeat): Repeat
    {
        $s = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $s->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        $start = clone $s;
        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeatYearly() . ' years');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * This function creates yearly relative roomy for a repeater
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    function createYearlyRelative(Repeat $repeat): Repeat
    {

        $s = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $s->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        $start = clone $s;
        $startTmp = clone $start;
        $startTmp->modify('first day of this year');
        $text = $this->number[$repeat->getRepeatYearlyRelativeNumber()] . ' ' . $this->days[$repeat->getRepeatYearlyRelativeWeekday()] . ' of ' . $this->months[$repeat->getRepeatYearlyRelativeMonth()];
        $startTmp->modify($text);
        $sollCounter = $repeat->getRepetation();
        $startTmp->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        if ($startTmp >= $start) {
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $sollCounter--;
            $start->modify('first day of this month');
            $start->modify('+' . ($repeat->getRepeatYearlyRelativeHowOften()) . ' years');
        } else {
            $start->modify('first day of next Year');
        }

        for ($i = 0; $i < $sollCounter; $i++) {
            $start->modify($text);
            $startTmp = clone $start;
            $startTmp->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start->modify('first day of this month');
            $start->modify('+' . ($repeat->getRepeatYearlyRelativeHowOften()) . ' years');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * This function clones the prototype and sets all paramters which are necesarry
     * @param Rooms $prototype
     * @param Repeat $repeat
     * @param \DateTime $start
     * @return Rooms
     * @author Emanuel Holzmann
     */
    function createClonedRoom(Rooms $prototype, Repeat $repeat, \DateTime $start): Rooms
    {

        $room = clone $prototype;
        foreach ($room->getUserAttributes() as $data) {
            $room->removeUserAttribute($data);
        }


        $room->setUid(rand(0, 999) . time());
        $room->setUidReal(md5(uniqid()));
        $room->setUidParticipant(md5(uniqid()));
        $room->setUidModerator(md5(uniqid()));
        $room->setRepeaterRemoved(false);
        $room->setRepeater($repeat);
        $room->setStart($start);
        $end = clone $start;
        $end->modify('+' . $prototype->getDuration() . ' min');
        $room->setEnddate($end);
        return $room;
    }


    /**
     * This function takes a new room and sets the new room as prototype in the repeater series which it belongs to.
     * @param Rooms $rooms
     * @return Repeat
     * @author Emanuel Holzmann
     */
    public function replaceRooms(Rooms $rooms): string
    {
        if (!$rooms->getRepeaterProtoype()) {
            return $this->translator->trans('Diese Aktion ist nicht erlaubt.');
        }
        $repeater = $this->prepareRepeater($rooms);
        //first show me the old repeater
        $repeater = $this->cleanRepeater($repeater);
        $repeater = $this->createNewRepeater($repeater);
        $this->addUserRepeat($repeater);
        $this->sendEMail($repeater, 'email/repeaterEdit.html.twig', $this->translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', ['{name}' => $repeater->getPrototyp()->getName()]), ['room' => $repeater->getPrototyp()]);
        $snack = $this->translator->trans('Sie haben erfolgreich einen Serientermin bearbeitet');

        // here we have the old prototype but with new Time and new Settings
        return $snack;
    }

    /**
     * @param Rooms $rooms
     * @return Repeat|null
     * This function Prepares the repeater to have the new startdate
     */
    public function prepareRepeater(Rooms $rooms)
    {

        $rooms->setEnddate((clone $rooms->getStart())->modify('+' . $rooms->getDuration() . 'min'));
        $this->em->persist($rooms);
        $this->em->flush();

        $repeater = $rooms->getRepeaterProtoype();
        $repeater->setStartDate($rooms->getStart());
        $this->em->persist($repeater);
        $this->em->flush();
        return $repeater;
    }

    /**
     * this function replaces the prototype in a repeater and hangs all attributes from the old prototype to the new prototype
     * @param Rooms $rooms
     * @param Repeat $repeat
     * @return Repeat
     * @author Emanuel Holzmann
     */
    private function replacePrototype(Rooms $rooms, Repeat $repeat): Repeat
    {
        $newPrototype = clone $rooms;
        $this->em->persist($newPrototype);
        $this->em->flush();
        $oldPrototype = $repeat->getPrototyp();
        foreach ($newPrototype->getPrototypeUsers() as $data) {
            $newPrototype->removePrototypeUser($data);
        }
        foreach ($oldPrototype->getPrototypeUsers() as $data) {
            $newPrototype->addPrototypeUser($data);
            $oldPrototype->removePrototypeUser($data);
        }
        foreach ($newPrototype->getUser() as $data) {
            $newPrototype->removeUser($data);
        }
        foreach ($newPrototype->getUserAttributes() as $data) {
            $newPrototype->removeUserAttribute($data);
            $this->em->remove($data);
        }
        foreach ($oldPrototype->getUserAttributes() as $data) {
            $newPrototype->addUserAttribute($data);
            $data->setRoom($newPrototype);
            $this->em->persist($data);
        }
        $newPrototype->setSequence(($newPrototype->getSequence()) + 1);

        foreach ($oldPrototype->getSchedulings() as $data2) {
            $oldPrototype->removeScheduling($data2);
            $this->em->remove($data2);
        }
        $repeat->removeRoom($newPrototype);
        $repeat->setPrototyp($newPrototype);
        $this->em->remove($oldPrototype);
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * remove all prototype user from the generated rooms which are not a prototype
     * @param Repeat $repeat
     * @author Emanuel Holzmann
     */
    private function cleanUp(Repeat $repeat)
    {
        foreach ($repeat->getRooms() as $data) {
            foreach ($data->getPrototypeUsers() as $data2) {
                $data->removePrototypeUser($data2);
            }
            $this->em->persist($data);
        }
        $this->em->flush();
    }

    /**
     * this function sends an email with the changes series
     * @param Repeat $repeat
     * @param $template
     * @param $subject
     * @param array $templateAttr
     * @param string $method
     * @param array $users
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @author Emanuel Holzmann
     */
    function sendEMail(Repeat $repeat, $template, $subject, $templateAttr = [], $method = 'REQUEST', $users = [])
    {
        if (sizeof($users) === 0) {
            $users = $repeat->getPrototyp()->getPrototypeUsers();
        }
        foreach ($users as $user) {
            $templateAttr['user'] = $user;
            $ics = $this->createIcs($repeat, $user);
            $attachement = [];
            $attachement[] = ['type' => 'text/calendar', 'filename' => $repeat->getPrototyp()->getName() . '.ics', 'body' => $ics];
            $this->mailer->sendEmail(
                $user,
                $subject,
                $this->twig->render($template, $templateAttr),
                $repeat->getPrototyp()->getServer(),
                $repeat->getPrototyp()->getModerator()->getEmail(),
                $repeat->getPrototyp(),
                $attachement
            );
        }
    }

    /**
     * this function creates the ICS for the series. this is a new calendar
     * @param Repeat $repeat
     * @param User $user
     * @return string
     * @author Emanuel Holzmann
     */
    private function createIcs(Repeat $repeat, User $user): string
    {
        $this->icalService->setRooms($repeat->getRooms()->toArray());
        return $this->icalService->getIcalString($user);
    }

    /**
     *
     * @param Repeat $repeat
     * @author Emanuel Holzmann
     */
    public function addUserRepeat(Repeat $repeat)
    {
        $prototype = $repeat->getPrototyp();
        foreach ($repeat->getRooms() as $data) {
            foreach ($data->getUser() as $data2) {
                $data->removeUser($data2);
            }
            $this->em->persist($data);
        }
        foreach ($repeat->getRooms() as $data) {
            foreach ($prototype->getPrototypeUsers() as $data2) {
                $data->addUser($data2);
            }
            $this->em->persist($data);
        }
        foreach ($repeat->getRooms() as $data) {
            foreach ($data->getUserAttributes() as $data2) {
                $data->removeUserAttribute($data2);
                $this->em->remove($data2);
            }
            $this->em->persist($data);
        }

        foreach ($repeat->getRooms() as $data) {
            foreach ($prototype->getUserAttributes() as $data2) {
                $tmp = clone $data2;
                $tmp->setRoom($data);
                $this->em->persist($tmp);
            }
        }
        $this->em->flush();
        $this->createNewCaller($repeat);
    }

    /**
     * @param Repeat $repeat
     * @return bool
     * @author Emanuel Holzmann
     */
    public function checkData(Repeat $repeat): bool
    {
        switch ($repeat->getRepeatType()) {
            case 0:
                if (!$repeat->getRepeaterDays()) {
                    return false;
                }
                break;
            case 1:
                if (!$repeat->getRepeaterWeeks()) {
                    return false;
                }
                break;
            case 2:
                if (!$repeat->getRepeatMontly()) {
                    return false;
                }
                break;
            case 3:
                if ($repeat->getRepatMonthRelativNumber() === null) {
                    return false;
                }
                if ($repeat->getRepatMonthRelativWeekday() === null) {
                    return false;
                }
                if ($repeat->getRepeatMonthlyRelativeHowOften() === null) {
                    return false;
                }
                break;
            case 4:
                if (!$repeat->getRepeatYearly()) {
                    return false;
                }
                break;
            case 5:
                if ($repeat->getRepeatYearlyRelativeHowOften() === null
                    || $repeat->getRepeatYearlyRelativeNumber() === null
                    || $repeat->getRepeatYearlyRelativeWeekday() === null
                    || $repeat->getRepeatYearlyRelativeMonth() === null
                ) {
                    return false;
                }
                break;
            default:
                break;
        }
        return true;
    }

    /**
     * @param Repeat $repeater
     * @return Repeat
     */
    public function cleanRepeater(Repeat $repeater)
    {

        if ($repeater->getPrototyp()->getCallerRoom()) {
            $callerRoom = $repeater->getPrototyp()->getCallerRoom();
            $this->em->remove($callerRoom);
            $this->em->flush();
        }
        $this->em->refresh($repeater);
        $this->em->refresh($repeater->getPrototyp());

        foreach ($repeater->getRooms() as $data) {
            foreach ($data->getUserAttributes() as $data2) {
                $data->removeUserAttribute($data2);
            }
            foreach ($data->getUser() as $data2) {
                $data2->removeRoom($data);
                $this->em->persist($data2);
            }
            $this->em->persist($data);
        }

        $this->em->flush();

        foreach ($repeater->getRooms() as $data) {
            $this->em->remove($data);
        }
        $repeater->getPrototyp()->setSequence(($repeater->getPrototyp()->getSequence()) + 1);
        $this->em->persist($repeater);
        $this->em->flush();
        foreach ($repeater->getPrototyp()->getCallerIds() as $data) {
            $repeater->getPrototyp()->removeCallerId($data);
        }
        $this->em->persist($repeater);
        $this->em->flush();


        return $repeater;
    }

    /**
     * @param Repeat $repeat
     * @return void
     * This Function creates the caller Id for each Room which is generated in the Repeater Session
     */
    public function createNewCaller(Repeat $repeat)
    {
        foreach ($repeat->getRooms() as $data) {
            $this->callerUserService->addCallerIdToRoom($data);
            $this->callerUserService->createUserCallerIDforRoom($data);
        }
    }
}
