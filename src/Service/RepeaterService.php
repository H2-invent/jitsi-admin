<?php

namespace App\Service;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\User;
use App\Enums\RepeatTypeEnum;
use App\Helper\UidGeneratorHelper;
use App\Service\Caller\CallerPrepareService;
use App\Service\Jigasi\JigasiService;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RepeaterService
{
    public function __construct(
        private readonly CallerPrepareService    $callerUserService,
        private readonly Environment             $twig,
        private readonly TranslatorInterface     $translator,
        private readonly MailerService           $mailer,
        private readonly EntityManagerInterface  $em,
        private readonly JoinUrlGeneratorService $joinUrlGeneratorService,
        private readonly JigasiService           $jigasiService
    ) {
    }

    /**
     * Creates all occurrences of the series from its prototype room. The
     * prototype itself stays untouched; every occurrence is a fresh room that
     * inherits the prototype's scalar settings and its current participants.
     */
    public function createNewRepeater(Repeat $repeat): Repeat
    {
        $prototype = $repeat->getPrototyp();
        [$start, $anchor] = $this->getInitialStartDate($repeat);

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $room = $this->createClonedRoom($prototype, $repeat, $start);
            $repeat->addRoom($room);
            $this->em->persist($room);
            [$start, $anchor] = $this->getNextStartDate($repeat, $start, $anchor);
        }

        // Users attached directly to the prototype room (edit flow) become the
        // series participants; addUserRepeat() spreads them over the occurrences.
        foreach ($prototype->getUser() as $user) {
            $prototype->addPrototypeUser($user);
            $prototype->removeUser($user);
        }

        $this->em->persist($repeat);
        $this->em->flush();

        return $repeat;
    }

    /**
     * Clones the prototype and sets all parameters which are necessary.
     */
    public function createClonedRoom(Rooms $prototype, Repeat $repeat, \DateTimeImmutable $start): Rooms
    {
        $room = clone $prototype;

        // An occurrence inherits the prototype's current participants as a snapshot;
        // addUserRepeat() later normalizes them from the series participants.
        foreach ($prototype->getUser() as $user) {
            $room->addUser($user);
        }

        $room->setUid(UidGeneratorHelper::generate());
        $room->setUidReal(UidGeneratorHelper::generate());
        $room->setUidParticipant(UidGeneratorHelper::generate());
        $room->setUidModerator(UidGeneratorHelper::generate());
        $room->setRepeaterRemoved(false);
        $room->setRepeater($repeat);
        $room->setStart($start);
        $end = $start->modify('+' . $prototype->getDuration() . ' min');
        $room->setEnddate($end);

        return $room;
    }

    /**
     * This function takes a new room and sets the new room as prototype in the repeater series which it belongs to.
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
        $this->sendEMail(
            $repeater,
            'email/repeaterEdit.html.twig',
            $this->translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', ['{name}' => $repeater->getPrototyp()->getName()]),
            ['room' => $repeater->getPrototyp()]
        );
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
     */
    public function sendEMail(
        Repeat           $repeat,
        string           $template,
        string           $subject,
        array            $templateAttr = [],
        string           $method = 'REQUEST',
        array|Collection $users = []
    ): void {
        if (sizeof($users) === 0) {
            $users = $repeat->getPrototyp()->getPrototypeUsers();
        }
        foreach ($users as $user) {
            $templateAttr['user'] = $user;
            $ics                  = $this->createIcs($repeat, $user, $method);

            $attachement   = [];
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
     */
    private function createIcs(Repeat $repeat, User $user, string $method = 'REQUEST'): string
    {
        $ics   = new IcsService();
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
                'uid'            => md5($repeat->getUid() ?? '') . '@' . parse_url($repeat->getPrototyp()->getHostUrl() ?? '', PHP_URL_HOST),
                'location'       => $this->translator->trans('meetling Konferenz'),
                'description'    => $description,
                'dtstart'        => $firstRoom->getStartUtc(),
                'dtend'          => $firstRoom->getEndDateUtc(),
                'summary'        => $repeat->getPrototyp()->getName(),
                'sequence'       => $repeat->getPrototyp()->getSequence(),
                'organizerEmail' => $repeat->getPrototyp()->getModerator()->getEmail(),
                'organizerName'  => $repeat->getPrototyp()->getModerator()->getFirstName() . ' ' . $repeat->getPrototyp()->getModerator()->getLastName(),
                'attendee'       => $user->getEmail(),
                'transp'         => 'OPAQUE',
                'rdate'          => $rDateString,
                'url'            => $repeat->getPrototyp()->getHostUrl(),
                'class'          => 'public'
            ]
        );


        foreach ($repeat->getRooms() as $room) {
            $description = $this->createDescription($room, $user);
            $url         = $this->joinUrlGeneratorService->generateUrl($room, $user);
            $ics->addEvent(
                [
                    'uid'           => md5($repeat->getUid() ?? '') . '@' . parse_url($repeat->getPrototyp()->getHostUrl() ?? '', PHP_URL_HOST),
                    'location'      => $this->translator->trans('meetling Konferenz'),
                    'description'   => $description,
                    'dtstart'       => $room->getStartUtc(),
                    'dtend'         => $room->getEndDateUtc(),
                    'summary'       => $room->getName(),
                    'sequence'      => $repeat->getPrototyp()->getSequence(),
                    'attendee'      => $user->getEmail(),
                    'transp'        => 'OPAQUE',
                    'url'           => $url,
                    'class'         => 'public',
                    'recurrence-id' => $ics->toUtcZ($room->getStartUtc()),
                ]
            );
        }

        return $ics->toString();
    }

    private function createDescription(Rooms $rooms, User $user): string
    {
        $url         = $this->joinUrlGeneratorService->generateUrl($rooms, $user);
        $description = $this->translator->trans("Sie wurden zu einer Videokonferenz eingeladen.");
        $description .= "\n\n";
        $description .= $this->translator->trans(
            "Über den beigefügten Link können Sie ganz einfach zur Videokonferenz beitreten.\nName: {name} \nModerator: {moderator} ",
            [
                "{name}"      => $rooms->getName(),
                "{moderator}" => $rooms->getModerator()->getFirstName() . " " . $rooms->getModerator()->getLastName()
            ]
        );
        $description .= ($rooms->getAgenda() ? "\n\n" . $this->translator->trans("Agenda") . ":\n" . implode(
            "\n",
            explode("\r\n", $rooms->getAgenda())
        ) . "\n\n" : "\n\n");
        $description .= $this->translator->trans(
            "Folgende Daten benötigen Sie um der Konferenz beizutreten:\nKonferenz ID: {id} \nIhre E-Mail-Adresse: {email}",
            ["{id}" => $rooms->getUid(), "{email}" => $user->getEmail()]
        );
        $description .= "\n\n" . $url . "\n\n" . $this->translator->trans("Sie erhalten diese E-Mail, weil Sie zu einer Videokonferenz eingeladen wurden.");
        if ($this->jigasiService->getRoomPin($rooms) && $this->jigasiService->getNumber($rooms)) {
            $description .= "\n\n\n" . $this->translator->trans("email.sip.text") . "\n";

            foreach ($this->jigasiService->getNumber($rooms) as $key => $value) {
                foreach ($value as $data) {
                    $description .= sprintf(
                        "(%s) %s %s: %s# (%s,,%s#) \n",
                        $key,
                        $data,
                        $this->translator->trans("email.sip.pin"),
                        $this->jigasiService->getRoomPin($rooms),
                        $data,
                        $this->jigasiService->getRoomPin($rooms)
                    );
                }
            }
        }

        return $description;
    }


    /**
     * Syncs the series participants and their room attributes to every
     * occurrence and creates the per-room dial-in data.
     */
    public function addUserRepeat(Repeat $repeat): void
    {
        $prototype = $repeat->getPrototyp();

        foreach ($repeat->getRooms() as $room) {
            foreach ($room->getUser() as $user) {
                $room->removeUser($user);
            }
            foreach ($room->getUserAttributes() as $attribute) {
                $room->removeUserAttribute($attribute);
                $this->em->remove($attribute);
            }
        }

        foreach ($repeat->getRooms() as $room) {
            foreach ($prototype->getPrototypeUsers() as $user) {
                $room->addUser($user);
            }
            foreach ($prototype->getUserAttributes() as $attribute) {
                $tmp = clone $attribute;
                $tmp->setRoom($room);
                $this->em->persist($tmp);
            }
        }

        $this->em->flush();
        $this->createNewCaller($repeat);
    }

    /**
     * Validates that every field required by the repeat type is set.
     */
    public function checkData(Repeat $repeat): bool
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
                if ($repeat->getRepeatYearlyRelativeHowOften() === null) {
                    return false;
                }
                if ($repeat->getRepeatYearlyRelativeNumber() === null) {
                    return false;
                }
                if ($repeat->getRepeatYearlyRelativeWeekday() === null) {
                    return false;
                }
                if ($repeat->getRepeatYearlyRelativeMonth() === null) {
                    return false;
                }
                break;
        }
        return true;
    }

    /**
     * Removes every occurrence of the series together with its dial-in data
     * and bumps the prototype sequence so calendar updates supersede older
     * invitations.
     */
    public function cleanRepeater(Repeat $repeater): Repeat
    {
        $prototype = $repeater->getPrototyp();

        if ($prototype->getCallerRoom()) {
            $this->em->remove($prototype->getCallerRoom());
        }
        foreach ($prototype->getCallerIds() as $callerId) {
            $prototype->removeCallerId($callerId);
            $this->em->remove($callerId);
        }

        foreach ($repeater->getRooms() as $room) {
            foreach ($room->getUserAttributes() as $attribute) {
                $room->removeUserAttribute($attribute);
                $this->em->remove($attribute);
            }
            foreach ($room->getUser() as $user) {
                $user->removeRoom($room);
            }
            $this->em->remove($room);
        }

        $prototype->setSequence($prototype->getSequence() + 1);
        $this->em->flush();

        $this->em->refresh($repeater);
        $this->em->refresh($prototype);

        return $repeater;
    }

    /**
     * Creates the per-room dial-in numbers and personal PINs for every
     * occurrence of the series.
     */
    public function createNewCaller(Repeat $repeat): void
    {
        $reservedIds = [];
        foreach ($repeat->getRooms() as $room) {
            $this->callerUserService->addCallerIdToRoom($room, $reservedIds);
        }
        $this->callerUserService->createUserCallerIDforRepeater($repeat);
    }

    /**
     * Returns the start of the first occurrence and the anchor the following
     * occurrence is computed from (null for interval-based repeat types).
     *
     * @return array{\DateTimeImmutable, \DateTimeImmutable|null}
     */
    private function getInitialStartDate(Repeat $repeat): array
    {
        $prototype = $repeat->getPrototyp();
        $start = $repeat->getStartDate()->setTime(
            (int) $prototype->getStart()->format('H'),
            (int) $prototype->getStart()->format('i')
        );

        if ($repeat->getRepeatType() === RepeatTypeEnum::MONTHLY_RELATIVE) {
            $firstOfMonth = $start->modify('first day of this month');
            $candidate = $this->applyRelativeText($repeat, $firstOfMonth);

            if ($candidate >= $start) {
                return [$candidate, $firstOfMonth->modify('+' . $repeat->getRepeatMonthlyRelativeHowOften() . ' months')];
            }

            $occurrence = $this->applyRelativeText($repeat, $start->modify('first day of next month'));

            return [$occurrence, $this->getNextRelativeAnchor($repeat, $occurrence)];
        }

        if ($repeat->getRepeatType() === RepeatTypeEnum::YEARLY_RELATIVE) {
            $firstOfYear = $start->modify('first day of this year');
            $candidate = $this->applyRelativeText($repeat, $firstOfYear);

            if ($candidate >= $start) {
                return [$candidate, $firstOfYear->modify('+' . $repeat->getRepeatYearlyRelativeHowOften() . ' years')];
            }

            $occurrence = $this->applyRelativeText($repeat, $start->modify('first day of next year'));

            return [$occurrence, $this->getNextRelativeAnchor($repeat, $occurrence)];
        }

        return [$start, null];
    }

    /**
     * Returns the start of the occurrence following $start and the anchor for
     * the occurrence after that.
     *
     * @return array{\DateTimeImmutable, \DateTimeImmutable|null}
     */
    private function getNextStartDate(Repeat $repeat, \DateTimeImmutable $start, ?\DateTimeImmutable $anchor): array
    {
        if ($anchor !== null) {
            $occurrence = $this->applyRelativeText($repeat, $anchor);

            return [$occurrence, $this->getNextRelativeAnchor($repeat, $occurrence)];
        }

        return [$start->modify($this->getIntervalModifier($repeat)), null];
    }

    /**
     * Relative repeat types are anchored to the first day of the month the
     * previous occurrence fell in; the next occurrence is howOften periods later.
     */
    private function getNextRelativeAnchor(Repeat $repeat, \DateTimeImmutable $occurrence): \DateTimeImmutable
    {
        if ($repeat->getRepeatType() === RepeatTypeEnum::MONTHLY_RELATIVE) {
            return $occurrence->modify('first day of this month')->modify('+' . $repeat->getRepeatMonthlyRelativeHowOften() . ' months');
        }

        return $occurrence->modify('first day of this month')->modify('+' . $repeat->getRepeatYearlyRelativeHowOften() . ' years');
    }

    /**
     * Applies the relative ordinal-weekday phrase and the prototype's start
     * time to the given date.
     */
    private function applyRelativeText(Repeat $repeat, \DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify($this->getRelativeText($repeat))->setTime(
            (int) $repeat->getPrototyp()->getStart()->format('H'),
            (int) $repeat->getPrototyp()->getStart()->format('i')
        );
    }

    /**
     * PHP relative-date phrase for the ordinal weekday, e.g. "Second Monday of this month".
     */
    private function getRelativeText(Repeat $repeat): string
    {
        if ($repeat->getRepeatType() === RepeatTypeEnum::MONTHLY_RELATIVE) {
            return $repeat->getRepatMonthRelativNumber()->label() . ' ' . $repeat->getRepatMonthRelativWeekday()->label() . ' of this month';
        }

        return $repeat->getRepeatYearlyRelativeNumber()->label() . ' ' . $repeat->getRepeatYearlyRelativeWeekday()->label() . ' of ' . $repeat->getRepeatYearlyRelativeMonth()->label();
    }

    /**
     * Relative repeat types are advanced via their anchor and never reach this method.
     */
    private function getIntervalModifier(Repeat $repeat): string
    {
        return match ($repeat->getRepeatType()) {
            RepeatTypeEnum::DAILY => '+' . $repeat->getRepeaterDays() . ' days',
            RepeatTypeEnum::WEEKLY => '+' . $repeat->getRepeaterWeeks() . ' weeks',
            RepeatTypeEnum::MONTHLY => '+' . $repeat->getRepeatMontly() . ' months',
            RepeatTypeEnum::YEARLY => '+' . $repeat->getRepeatYearly() . ' years',
            RepeatTypeEnum::MONTHLY_RELATIVE,
            RepeatTypeEnum::YEARLY_RELATIVE => throw new \LogicException('Relative repeat types are advanced via their anchor.'),
            null => throw new \LogicException('A repeat type is required to generate a series.'),
        };
    }
}
