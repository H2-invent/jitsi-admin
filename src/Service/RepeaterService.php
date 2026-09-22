<?php

namespace App\Service;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use App\Enums\RepeatTypeEnum;
use App\Service\caller\CallerPrepareService;
use App\Service\Jigasi\JigasiService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Guides\RestructuredText\Directives\Replace;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RepeaterService
{
    private EntityManagerInterface $em;
    private MailerService $mailer;



    private TranslatorInterface $translator;
    private Environment $twig;
    private CallerPrepareService $callerUserService;

    public function __construct(
        CallerPrepareService            $callerPrepareService,

        Environment                     $environment,
        TranslatorInterface             $translator,
        MailerService                   $mailerService,
        EntityManagerInterface          $entityManager,
        private JoinUrlGeneratorService $joinUrlGeneratorService,
        private JigasiService $jigasiService,
    )
    {
        $this->em = $entityManager;
        $this->mailer = $mailerService;
        $this->translator = $translator;
        $this->twig = $environment;

        $this->callerUserService = $callerPrepareService;
    }

    /**
     * @author Emanuel Holzmann
     */
    function createNewRepeater(Repeat $repeat): Repeat
    {

        $userAttribute = $repeat->getPrototyp()->getUserAttributes()->toArray();
        switch ($repeat->getRepeatType()) {
            case RepeatTypeEnum::DAILY:
                $repeat = $this->createDaily($repeat);
                break;
            case RepeatTypeEnum::WEEKLY:
                $repeat = $this->createWeekly($repeat);
                break;
            case RepeatTypeEnum::MONTHLY:
                $repeat = $this->createMontly($repeat);
                break;
            case RepeatTypeEnum::MONTHLY_RELATIVE:
                $repeat = $this->createMontlyRelative($repeat);
                break;
            case RepeatTypeEnum::YEARLY:
                $repeat = $this->createYearly($repeat);
                break;
            case RepeatTypeEnum::YEARLY_RELATIVE:
                $repeat = $this->createYearlyRelative($repeat);
                break;
        }
        foreach ($userAttribute as $data) {
            $repeat->getPrototyp()->addUserAttribute($data);
        }
        foreach ($repeat->getPrototyp()->getUser() as $data) {
            $repeat->getPrototyp()->addPrototypeUser($data);
        }

        return $repeat;
    }


    /**
     * @author Emanuel Holzmann
     */
    function createDaily(Repeat $repeat): Repeat
    {
        //hier bauen wir alle X tage einen neuenRoom
        $start = $repeat->getStartDate();
        $prototype = $this->em->getRepository(Rooms::class)->find($repeat->getPrototyp()->getId());
        $start = $start->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $room = $this->createClonedRoom($prototype, $repeat, $start);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start = $start->modify('+' . $repeat->getRepeaterDays() . ' days');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @author Emanuel Holzmann
     */
    function createWeekly(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start = $start->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $room = $this->createClonedRoom($prototype, $repeat, $start);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start = $start->modify('+' . $repeat->getRepeaterWeeks() . ' weeks');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @author Emanuel Holzmann
     */
    function createMontly(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start = $start->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $room = $this->createClonedRoom($prototype, $repeat, $start);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start = $start->modify('+' . $repeat->getRepeatMontly() . ' months');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @author Emanuel Holzmann
     */
    function createMontlyRelative(Repeat $repeat): Repeat
    {

        $s = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start = $s->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
        $startTmp = $start->modify('first day of this month');
        $text = $repeat->getRepatMonthRelativNumber()->label() . ' ' . $repeat->getRepatMonthRelativWeekday()->label() . ' of this month';
        $startTmp = $startTmp->modify($text);
        $startTmp = $startTmp->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
        $sollCounter = $repeat->getRepetation();
        if ($startTmp >= $start) {
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start = $start->modify('first day of this month');
            $start = $start->modify('+' . ($repeat->getRepeatMonthlyRelativeHowOften()) . ' months');
            $sollCounter--;
        } else {
            $start = $start->modify('first day of next Month');
        }

        for ($i = 0; $i < $sollCounter; $i++) {
            $start = $start->modify($text);
            $startTmp = $start->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $repeat->addRoom($room);
            $start = $start->modify('first day of this month');
            $start = $start->modify('+' . ($repeat->getRepeatMonthlyRelativeHowOften()) . ' months');
        }

        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * @author Emanuel Holzmann
     */
    function createYearly(Repeat $repeat): Repeat
    {
        $s = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start = $s->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $room = $this->createClonedRoom($prototype, $repeat, $start);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start = $start->modify('+' . $repeat->getRepeatYearly() . ' years');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * This function creates yearly relative roomy for a repeater
     * @author Emanuel Holzmann
     */
    function createYearlyRelative(Repeat $repeat): Repeat
    {

        $s = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start = $s->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
        $startTmp = $start->modify('first day of this year');
        $text = $repeat->getRepeatYearlyRelativeNumber()->label() . ' ' . $repeat->getRepeatYearlyRelativeWeekday()->label() . ' of ' . $repeat->getRepeatYearlyRelativeMonth()->label();
        $startTmp = $startTmp->modify($text);
        $sollCounter = $repeat->getRepetation();
        $startTmp = $startTmp->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
        if ($startTmp >= $start) {
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $sollCounter--;
            $start = $start->modify('first day of this month');
            $start = $start->modify('+' . ($repeat->getRepeatYearlyRelativeHowOften()) . ' years');
        } else {
            $start = $start->modify('first day of next Year');
        }

        for ($i = 0; $i < $sollCounter; $i++) {
            $start = $start->modify($text);
            $startTmp = $start->setTime((int) $prototype->getStart()->format('H'), (int) $prototype->getStart()->format('i'));
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $repeat->addRoom($room);
            $this->em->persist($room);
            $start = $start->modify('first day of this month');
            $start = $start->modify('+' . ($repeat->getRepeatYearlyRelativeHowOften()) . ' years');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    /**
     * This function clones the prototype and sets all paramters which are necesarry
     * @author Emanuel Holzmann
     */
    function createClonedRoom(Rooms $prototype, Repeat $repeat, \DateTimeImmutable $start): Rooms
    {

        $room = clone $prototype;
        foreach ($room->getUserAttributes() as $data) {
            $room->removeUserAttribute($data);
        }


        $room->setUid(md5(uniqid()));
        $room->setUidReal(md5(uniqid()));
        $room->setUidParticipant(md5(uniqid()));
        $room->setUidModerator(md5(uniqid()));
        $room->setRepeaterRemoved(false);
        $room->setRepeater($repeat);
        $room->setStart($start);
        $end = $start->modify('+' . $prototype->getDuration() . ' min');
        $room->setEnddate($end);
        return $room;
    }


    /**
     * This function takes a new room and sets the new room as prototype in the repeater series which it belongs to.
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
     * @return Repeat|null
     * This function Prepares the repeater to have the new startdate
     */
    public function prepareRepeater(Rooms $rooms): ?Repeat
    {

        $rooms->setEnddate($rooms->getStart()->modify('+' . $rooms->getDuration() . 'min'));
        $this->em->persist($rooms);
        $this->em->flush();

        $repeater = $rooms->getRepeaterProtoype();
        $repeater->setStartDate($rooms->getStart());
        $this->em->persist($repeater);
        $this->em->flush();
        return $repeater;
    }

    /**
     * this function sends an email with the changes series
     * @param array<string, mixed> $templateAttr
     * @param array<User>|Collection<int, User> $users
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @author Emanuel Holzmann
     */
    function sendEMail(Repeat $repeat, string $template, string $subject, array $templateAttr = [], string $method = 'REQUEST', array|Collection $users = []): void
    {
        if (sizeof($users) === 0) {
            $users = $repeat->getPrototyp()->getPrototypeUsers();
        }
        foreach ($users as $user) {
            $templateAttr['user'] = $user;
            $ics = $this->createIcs($repeat, $user,$method);

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
     * @author Emanuel Holzmann
     */
    private function createIcs(Repeat $repeat, User $user, string $method = 'REQUEST'): string
    {
        $ics = new IcsService();
        $rooms = $repeat->getRooms();
        $rDate = [];
        foreach ($rooms as $room) {
            $rDate[] = $ics->toUtcZ($room->getStartUtc());
        }
        $rDateString = implode(',', $rDate);

        if ($repeat->getPrototyp()->getModerator() === $user && $method !== 'CANCEL') {
            $method = 'PUBLISH';

        }
        $ics->setMethod($method);

        $description = $this->translator->trans("Sie wurden zu einer Videokonferenz hinzugefügt.") .
            "\n\n" .
            $this->translator->trans("Jede Konferenz hat einen eigenen Link den Sie zum beitreten anklicken müssen.");

        //this is the main event and holds all the Rdate.
        // The Rdates will be overwritten by the individall elements

        /** @var Rooms $firstRoom */
        $firstRoom = $repeat->getRooms()->first();
        $ics->addEvent(
            [
                'uid' => md5($repeat->getUid()) . '@' . parse_url($repeat->getPrototyp()->getHostUrl(), PHP_URL_HOST),
                'location' => $this->translator->trans('meetling Konferenz'),
                'description' => $description,
                'dtstart' => $firstRoom->getStartUtc(),
                'dtend' => $firstRoom->getEndDateUtc(),
                'summary' => $repeat->getPrototyp()->getName(),
                'sequence' => $repeat->getPrototyp()->getSequence(),
                'organizerEmail'=>$repeat->getPrototyp()->getModerator()->getEmail(),
                'organizerName'=>$repeat->getPrototyp()->getModerator()->getFirstName() .' '. $repeat->getPrototyp()->getModerator()->getLastName(),
                'attendee' => $user->getEmail(),
                'transp' => 'OPAQUE',
                'rdate' => $rDateString,
                'url' => $repeat->getPrototyp()->getHostUrl(),
                'class' => 'public'
            ]
        );


        foreach ($repeat->getRooms() as $room) {
            $description = $this->createDescription($room, $user);
            $url = $this->joinUrlGeneratorService->generateUrl($room, $user);
            $ics->addEvent(
                [
                    'uid' => md5($repeat->getUid()) . '@' . parse_url($repeat->getPrototyp()->getHostUrl(), PHP_URL_HOST),
                    'location' => $this->translator->trans('meetling Konferenz'),
                    'description' => $description,
                    'dtstart' => $room->getStartUtc(),
                    'dtend' => $room->getEndDateUtc(),
                    'summary' => $room->getName(),
                    'sequence' => $repeat->getPrototyp()->getSequence(),
                    'attendee' => $user->getEmail(),
                    'transp' => 'OPAQUE',
                    'url' => $url,
                    'class' => 'public',
                    'recurrence-id' => $ics->toUtcZ($room->getStartUtc()),
                ]
            );

        }


        return $ics->toString();

    }

    private function createDescription(Rooms $rooms, User $user): string
    {

        $url = $this->joinUrlGeneratorService->generateUrl($rooms, $user);
        $description =  $this->translator->trans("Sie wurden zu einer Videokonferenz eingeladen.") .
            "\n\n" .
            $this->translator->trans("Über den beigefügten Link können Sie ganz einfach zur Videokonferenz beitreten.\nName: {name} \nModerator: {moderator} ", ["{name}" => $rooms->getName(), "{moderator}" => $rooms->getModerator()->getFirstName() . " " . $rooms->getModerator()->getLastName()])
            . ($rooms->getAgenda() ? "\n\n" . $this->translator->trans("Agenda") . ":\n" . implode("\n", explode("\r\n", $rooms->getAgenda())) . "\n\n" : "\n\n") .
            $this->translator->trans("Folgende Daten benötigen Sie um der Konferenz beizutreten:\nKonferenz ID: {id} \nIhre E-Mail-Adresse: {email}", ["{id}" => $rooms->getUid(), "{email}" => $user->getEmail()])
            . "\n\n" .
            $url .
            "\n\n" .
            $this->translator->trans("Sie erhalten diese E-Mail, weil Sie zu einer Videokonferenz eingeladen wurden.");
        if ($this->jigasiService->getRoomPin($rooms) && $this->jigasiService->getNumber($rooms)) {
            $description = $description . "\n\n\n" . $this->translator->trans("email.sip.text") . "\n";

            foreach ($this->jigasiService->getNumber($rooms) as $key => $value) {
                foreach ($value as $data) {
                    $description = $description
                        . sprintf("(%s) %s %s: %s# (%s,,%s#) \n", $key, $data, $this->translator->trans("email.sip.pin"), $this->jigasiService->getRoomPin($rooms), $data, $this->jigasiService->getRoomPin($rooms));
                }
            }
        }
        return $description;
    }


    /**
     *
     * @author Emanuel Holzmann
     */
    public
    function addUserRepeat(Repeat $repeat): void
    {
        $prototype = $repeat->getPrototyp();
        foreach ($repeat->getRooms() as $data) {//iterate over all rooms in the series
            foreach ($data->getUser() as $data2) {//remove all participants from al rooms
                $data->removeUser($data2);
            }
            $this->em->persist($data);
        }
        foreach ($repeat->getRooms() as $data) {// iterate over all rooms
            foreach ($prototype->getPrototypeUsers() as $data2) {//add all participants from the prototype to all rooms in the series
                $data->addUser($data2);
            }
            $this->em->persist($data);
        }
        foreach ($repeat->getRooms() as $data) {//iterate ovre all rooms in the series
            foreach ($data->getUserAttributes() as $data2) {//remove all user ttributes like moderatators to all rooms in the series
                $data->removeUserAttribute($data2);
                $this->em->remove($data2);
            }
            $this->em->persist($data);
        }

        foreach ($repeat->getRooms() as $data) {//iterate over all rooms in the series
            foreach ($prototype->getUserAttributes() as $data2) {//add all attributes to the rooms
                $tmp = clone $data2;
                $tmp->setRoom($data);
                $this->em->persist($tmp);
            }
        }
        $this->em->flush();
        $this->createNewCaller($repeat);
    }

    /**
     * @author Emanuel Holzmann
     */
    public
    function checkData(Repeat $repeat): bool
    {
        switch ($repeat->getRepeatType()) {
            case RepeatTypeEnum::DAILY:
                if (!$repeat->getRepeaterDays()) {
                    return false;
                }
                break;
            case RepeatTypeEnum::WEEKLY:
                if (!$repeat->getRepeaterWeeks()) {
                    return false;
                }
                break;
            case RepeatTypeEnum::MONTHLY:
                if (!$repeat->getRepeatMontly()) {
                    return false;
                }
                break;
            case RepeatTypeEnum::MONTHLY_RELATIVE:
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
            case RepeatTypeEnum::YEARLY:
                if (!$repeat->getRepeatYearly()) {
                    return false;
                }
                break;
            case RepeatTypeEnum::YEARLY_RELATIVE:
                if ($repeat->getRepeatYearlyRelativeHowOften() === null
                    || $repeat->getRepeatYearlyRelativeNumber() === null
                    || $repeat->getRepeatYearlyRelativeWeekday() === null
                    || $repeat->getRepeatYearlyRelativeMonth() === null
                ) {
                    return false;
                }
                break;
        }
        return true;
    }

    public
    function cleanRepeater(Repeat $repeater): Repeat
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
     * @return void
     * This Function creates the caller Id for each Room which is generated in the Repeater Session
     */
    public
    function createNewCaller(Repeat $repeat): void
    {
        foreach ($repeat->getRooms() as $data) {
            $this->callerUserService->addCallerIdToRoom($data);

        }
        $this->callerUserService->createUserCallerIDforRepeater($repeat);
    }
}
