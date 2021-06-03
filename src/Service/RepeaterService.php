<?php


namespace App\Service;


use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Guides\RestructuredText\Directives\Replace;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RepeaterService
{
    private $em;
    private $mailer;
    private $icsService;
    private $icalService;
    private $userService;
    private $translator;
    private $twig;
    private $days = array(
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday'
    );
    private $number = array(
        0 => 'First',
        1 => 'Second',
        2 => 'Third',
        3 => 'Fourth',
        4 => 'Fifth',
        5 => 'Last',
    );
    private $months = array(
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
    );

    public function __construct(IcalService $icalService, Environment $environment, TranslatorInterface $translator, UserService $userService, IcsService $icsService, MailerService $mailerService, EntityManagerInterface $entityManager  )
    {
        $this->icsService = $icsService;
        $this->em = $entityManager;

        $this->mailer = $mailerService;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->twig = $environment;
        $this->icalService = $icalService;
    }

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

    function createDaily(Repeat $repeat): Repeat
    {
        //hier bauen wir alle X tage einen neuenRoom
        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
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

    public function changeRooms(Repeat $repeat, Rooms $prototype): Repeat
    {
        foreach ($repeat->getRooms() as $data) {
            if (!$data->getRepeaterRemoved()) {
                $room = clone $prototype;
                $room->setUid($data->getUid());
                $room->setUidReal($data->getUidReal());
                $room->setUidParticipant($data->getUidParticipant());
                $room->setUidModerator($data->getUidModerator());
                $room->setRepeater($repeat);
                $room->setStart($data->getStart()->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i')));
                $end = clone $room->getStart();
                $end->modify('+' . $room->getDuration() . ' min');
                $room->setEnddate($end);
                foreach ($data->getUser() as $data2) {
                    $data->removeUser($data2);
                }
                foreach ($data->getUserAttributes() as $data2) {
                    $data->removeUserAttribute($data2);
                    $this->em->remove($data2);
                }
                foreach ($data->getSchedulings() as $data2){
                    $data->removeScheduling($data2);
                    $this->em->remove($data2);
                }
                $this->em->persist($room);
                $repeat->removeRoom($data);
                $this->em->remove($data);
                $repeat->addRoom($room);
            }
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    public function replaceRooms(Rooms $rooms):Repeat
    {

        $repeater = $rooms->getRepeater();
        $oldProto = $repeater->getPrototyp();
        $repeater = $this->replacePrototype($rooms, $repeater);
        $repeater = $this->changeRooms($repeater, $repeater->getPrototyp());
        $this->addUserRepeat($repeater);
        $this->cleanUp($repeater);
        return $repeater;
    }

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
        $newPrototype->setSequence(($newPrototype->getSequence())+1);

        foreach ($oldPrototype->getSchedulings() as $data2){
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

    function sendEMail(Repeat $repeat, $template, $subject, $templateAttr = array(), $method = 'REQUEST',$users = array())
    {
        if(sizeof($users) === 0){
            $users = $repeat->getPrototyp()->getPrototypeUsers();
        }
        foreach ($users as $user) {
            $templateAttr['user'] = $user;
            $ics = $this->createIcs($repeat, $user);
            $attachement = array();
            $attachement[] = array('type' => 'text/calendar', 'filename' => $repeat->getPrototyp()->getName() . '.ics', 'body' => $ics);
            $this->mailer->sendEmail(
                $user->getEmail(), $subject,
                $this->twig->render($template, $templateAttr),
                $repeat->getPrototyp()->getServer(),
                $attachement
            );
        }
    }

    private function createIcs(Repeat $repeat, User $user):string
    {
      return $this->icalService->getIcalStringfromRepeater($repeat,$user);
    }

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
                $data->addUserAttribute($tmp);
                $this->em->persist($tmp);
            }
            $this->em->persist($data);
        }
        $this->em->flush();
    }
    public function checkData(Repeat $repeat):bool
    {
        switch ($repeat->getRepeatType()) {
            case 0:
                if (!$repeat->getRepeaterDays()){
                    return false;
                }
                break;
            case 1:
                if (!$repeat->getRepeaterWeeks()){
                    return false;
                }
                break;
            case 2:
                if (!$repeat->getRepeatMontly()){
                    return false;
                }
                break;
            case 3:
                if ($repeat->getRepatMonthRelativNumber()=== null ){
                    return false;
                }
                if ($repeat->getRepatMonthRelativWeekday()=== null){
                    return false;
                }
                if ($repeat->getRepeatMonthlyRelativeHowOften()=== null){
                    return false;
                }
                break;
            case 4:
                if (!$repeat->getRepeatYearly() ){
                    return false;
                }
                break;
            case 5:
                if ($repeat->getRepeatYearlyRelativeHowOften()=== null ||
                    $repeat->getRepeatYearlyRelativeNumber()=== null ||
                    $repeat->getRepeatYearlyRelativeWeekday()=== null ||
                    $repeat->getRepeatYearlyRelativeMonth()=== null){
                    return false;
                }
                break;
            default:
                break;

        }
        return true;
    }
}
