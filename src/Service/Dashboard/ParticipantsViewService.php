<?php

declare(strict_types=1);

namespace App\Service\Dashboard;

use App\Entity\CallerId;
use App\Entity\LdapUserProperties;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use App\Repository\RoomsUserRepository;
use App\Service\FormatName;
use App\Service\Jigasi\JigasiService;
use App\Service\ParticipantSearchService;
use App\Service\Theme\ThemeService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Builds the structured JSON view-model for the React "Manage participants" modal
 * on the dashboard.
 *
 * All business rules stay server-side: the React application receives the room's
 * invitees together with pre-computed capability flags, urls, translated labels and
 * per-user action descriptors and renders them. Mutations are performed through the
 * existing participant endpoints which answer with JSON.
 */
class ParticipantsViewService
{
    private const DEFAULT_AVATAR_URL = 'build/images/defaultUser.8f87824e.webp';

    /** @var array<int, array<int|string, mixed>> */
    private array $jigasiNumberCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormatName $formatName,
        private readonly ParticipantSearchService $participantSearchService,
        private readonly JigasiService $jigasiService,
        private readonly ThemeService $themeService,
        private readonly UploaderHelper $uploaderHelper,
        private readonly Packages $assets,
    ) {
    }

    /**
     * Serializes everything the dashboard participant management modal needs for a
     * single room. Access is validated by the caller (the dashboard API controller).
     */
    public function buildState(User $user, Rooms $room): array
    {
        $sipShownInFrontend = (int) $this->themeService->getApplicationProperties('SIP_CALLER_SHOW_IN_FRONTEND') === 1;
        $isScheduleMeeting = (bool) $room->getScheduleMeeting();

        $organizer = $room->getModerator();

        // Load the permission rows for all invitees in one query instead of one
        // repository lookup per participant.
        $roomsUsers = [];
        foreach ($this->em->getRepository(RoomsUser::class)->findBy(['room' => $room]) as $roomsUser) {
            $roomsUserUser = $roomsUser->getUser();
            if ($roomsUserUser !== null) {
                $roomsUsers[$roomsUserUser->getId()] = $roomsUser;
            }
        }

        // Participants whose LDAP group forbids promotion are resolved in a single
        // query so that serializing each invitee does not trigger one lazy proxy
        // load of User::ldapUserProperties per row.
        $promotionBlockedUserIds = $this->promotionBlockedUserIds($room);

        $participants = [];
        foreach ($room->getUser() as $participant) {
            if ($participant === $organizer) {
                continue;
            }
            $participants[] = $this->buildParticipant(
                $user,
                $room,
                $participant,
                false,
                $roomsUsers,
                $promotionBlockedUserIds
            );
        }
        usort($participants, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        $waitinglist = [];
        foreach ($room->getWaitinglists() as $entry) {
            $entryUser = $entry->getUser();
            if ($entryUser === null) {
                continue;
            }
            $waitinglist[] = [
                'id' => $entry->getId(),
                'email' => (string) $entryUser->getEmail(),
                'acceptUrl' => $this->url('accept_waitingList', ['id' => $entry->getId()]),
            ];
        }

        return [
            'roomId' => $room->getId(),
            'title' => $this->translator->trans('Teilnehmer verwalten'),
            'addUrl' => $this->url('room_add_user_single', ['room' => $room->getId()]),
            'bulkAddUrl' => $this->url('room_add_user_bulk', ['room' => $room->getId()]),
            'searchUrl' => $this->url('search_participant'),
            'allowBulkInvite' => (int) $this->env('laf_addParticipantsNoInput') !== 1,
            'canPrintParticipants' => $sipShownInFrontend && !$isScheduleMeeting,
            'printUrl' => $sipShownInFrontend && !$isScheduleMeeting
                ? $this->url('app_download_participants_list', ['room' => $room->getId()])
                : null,
            'organizer' => $organizer !== null
                ? $this->buildParticipant($user, $room, $organizer, true, $roomsUsers)
                : null,
            'participants' => $participants,
            'waitinglist' => $waitinglist,
            'translations' => $this->buildTranslations(),
        ];
    }

    /**
     * Serializes one invitee of the room, including the per-user dropdown actions
     * (replicates the logic of the former room/attendee dropdown partials).
     */
    public function buildParticipant(
        User $user,
        Rooms $room,
        User $participant,
        bool $isOrganizer,
        array $roomsUsers = [],
        array $promotionBlockedUserIds = []
    ): array {
        $sipShownInFrontend = (int) $this->themeService->getApplicationProperties('SIP_CALLER_SHOW_IN_FRONTEND') === 1;
        $isScheduleMeeting = (bool) $room->getScheduleMeeting();
        $sipVisible = $sipShownInFrontend && !$isScheduleMeeting;

        $nameNoIcon = $this->participantSearchService->buildShowInFrontendStringNoString($participant);
        $showNameFrontend = $this->themeService->getApplicationProperties('laf_showNameFrontend');
        if (!is_string($showNameFrontend) || trim($showNameFrontend) === '') {
            $showNameFrontend = '$user.username$';
        }
        $name = $this->formatName->formatName($showNameFrontend, $participant);
        if ($name === '') {
            $name = $nameNoIcon;
        }

        $permissions = $roomsUsers[$participant->getId()] ?? new RoomsUser();
        $profilePicture = $participant->getProfilePicture() !== null
            ? $this->uploaderHelper->asset($participant->getProfilePicture(), 'documentFile')
            : $this->assets->getUrl(self::DEFAULT_AVATAR_URL);

        $participantModel = [
            'id' => $participant->getId(),
            'uid' => $participant->getUid(),
            'name' => $name,
            'username' => $participant->getUsername(),
            'profilePicture' => $profilePicture,
            'isCurrentUser' => $participant === $user,
            'isOrganizer' => $isOrganizer,
            'permissions' => [
                'moderator' => $permissions->getModerator() === true,
                'shareDisplay' => $permissions->getShareDisplay() === true,
                'privateMessage' => $permissions->getPrivateMessage() === true,
                'lobbyModerator' => $permissions->getLobbyModerator() === true,
            ],
            'sip' => null,
            'actions' => [],
        ];

        $sipData = $sipVisible ? $this->sipData($room, $participant) : null;
        if ($sipData !== null) {
            $participantModel['sip'] = $sipData;
        }

        if ($isOrganizer) {
            // The organizer row only carries the SIP dial-in details dropdown.
            if ($sipData !== null) {
                $participantModel['actions'][] = $this->sipAction($participant);
            }
            return $participantModel;
        }

        $participantModel['actions'] = $this->participantActions(
            $user,
            $room,
            $participant,
            $permissions,
            $sipData,
            $promotionBlockedUserIds
        );

        return $participantModel;
    }

    /**
     * Dropdown menu items for a regular invitee (replicates the logic of the former
     * attendee dropdown partials).
     *
     * @return array<int, array<string, mixed>>
     */
    private function participantActions(
        User $user,
        Rooms $room,
        User $participant,
        RoomsUser $permissions,
        ?array $sipData,
        array $promotionBlockedUserIds = []
    ): array {
        $actions = [];

        if (!isset($promotionBlockedUserIds[$participant->getId()])) {
            $isModerator = $permissions->getModerator() === true;
            $actions[] = [
                'key' => 'moderator',
                'label' => $this->translator->trans('Moderator'),
                'icon' => 'fa fa-crown',
                'href' => $this->url('room_add_moderator', [
                    'room' => $room->getId(),
                    'user' => $participant->getId(),
                ]),
                'active' => $isModerator,
                'tooltip' => $this->translator->trans('Zum Moderator ernennen'),
            ];

            if ((bool) $room->getDissallowScreenshareGlobal()) {
                $shareDisplay = $permissions->getShareDisplay() === true;
                $actions[] = [
                    'key' => 'shareScreen',
                    'label' => $this->translator->trans('Erlauben seinen Desktop zu teilen'),
                    'icon' => 'fas fa-desktop',
                    'href' => $this->url('change_permissions_screenShare', [
                        'room' => $room->getId(),
                        'user' => $participant->getId(),
                    ]),
                    'active' => $shareDisplay,
                    'tooltip' => $this->translator->trans('Erlauben seinen Desktop zu teilen'),
                ];
            }

            if ((bool) $room->getDissallowPrivateMessage()) {
                $privateMessage = $permissions->getPrivateMessage() === true;
                $actions[] = [
                    'key' => 'privateMessage',
                    'label' => $this->translator->trans('Private Nachrichten'),
                    'icon' => 'far fa-comments',
                    'href' => $this->url('change_permissions_privateMessage', [
                        'room' => $room->getId(),
                        'user' => $participant->getId(),
                    ]),
                    'active' => $privateMessage,
                ];
            }

            if ((bool) $room->getLobby()) {
                $lobbyModerator = $permissions->getLobbyModerator() === true;
                $actions[] = [
                    'key' => 'lobbyModerator',
                    'label' => $this->translator->trans('Lobbymoderator'),
                    'icon' => 'fas fa-couch',
                    'href' => $this->url('room_add_lobby_moderator', [
                        'room' => $room->getId(),
                        'user' => $participant->getId(),
                    ]),
                    'active' => $lobbyModerator,
                    'confirmText' => $lobbyModerator
                        ? $this->translator->trans('addParticipants.lobbyModerator.remove')
                        : $this->translator->trans('addParticipants.lobbyModerator.add'),
                    'tooltip' => $this->translator->trans('addParticipants.lobbyModerator.help'),
                ];
            }
        }

        if ((int) $this->env('laf_show_resendInvitation') === 1) {
            $actions[] = [
                'key' => 'resend',
                'label' => $this->translator->trans('participant.resend.invitation'),
                'icon' => 'fas fa-share-square',
                'href' => $this->url('room_user_resend', [
                    'room' => $room->getUidReal(),
                    'user' => $participant->getId(),
                ]),
            ];
        }

        if ($permissions->getModerator() === true
            && $room->getModerator() === $user
            && $participant->getKeycloakId() !== null
        ) {
            $actions[] = [
                'key' => 'transfer',
                'label' => $this->translator->trans('transfer.room.start'),
                'icon' => 'fa-solid fa-arrow-right-arrow-left',
                'href' => $this->url('room_change_ownership_index', [
                    'roomId' => $room->getId(),
                    'newOwner' => $participant->getId(),
                ]),
                'confirmText' => $this->translator->trans('transfer.room.confirm'),
            ];
        }

        if ($sipData !== null) {
            $actions[] = $this->sipAction($participant);
        }

        $actions[] = [
            'key' => 'delete',
            'label' => $this->translator->trans('Löschen'),
            'icon' => 'fa fa-trash',
            'href' => $this->url('room_user_remove', [
                'room' => $room->getId(),
                'user' => $participant->getId(),
            ]),
            'confirmText' => $this->translator->trans('Wollen Sie den Teilnehmer wirklich löschen?'),
        ];

        return $actions;
    }

    /**
     * SIP dial-in information for one user (replicates the logic of the former
     * attendee SIP dropdown partial).
     */
    private function sipData(Rooms $room, User $participant): ?array
    {
        $pin = $this->sipPinFromRoomAndUser($room, $participant);
        $callerRoom = $room->getCallerRoom();
        if ($pin === null || $callerRoom === null) {
            return null;
        }

        $numbers = $this->jigasiNumbers($room);
        $formattedNumbers = [];
        if (is_array($numbers)) {
            foreach ($numbers as $key => $numberSet) {
                if (!is_array($numberSet)) {
                    continue;
                }
                foreach ($numberSet as $number) {
                    $formattedNumbers[] = '(' . (string) $key . ') ' . (string) $number;
                }
            }
        }

        return [
            'numbers' => $formattedNumbers,
            'roomNumber' => trim(chunk_split((string) $callerRoom->getCallerId(), 3, ' ')),
            'pin' => trim(chunk_split((string) $pin->getCallerId(), 3, ' ')),
        ];
    }

    /**
     * The SIP dial-in numbers belong to the room, not to the single invitee. They are
     * resolved once per room and request.
     *
     * @return array<int|string, mixed>
     */
    private function jigasiNumbers(Rooms $room): array
    {
        $key = $room->getId();
        if (!array_key_exists($key, $this->jigasiNumberCache)) {
            $numbers = $this->jigasiService->getNumber($room);
            $this->jigasiNumberCache[$key] = is_array($numbers) ? $numbers : [];
        }
        return $this->jigasiNumberCache[$key];
    }

    /**
     * @return array<string, mixed>
     */
    private function sipAction(User $participant): array
    {
        return [
            'key' => 'sip',
            'label' => $this->translator->trans('sip.caller.data'),
            'icon' => 'fas fa-phone',
            'type' => 'sip',
        ];
    }

    private function sipPinFromRoomAndUser(Rooms $rooms, User $user): ?CallerId
    {
        foreach ($user->getCallerIds() as $callerId) {
            if ($callerId->getRoom() === $rooms) {
                return $callerId;
            }
        }
        return null;
    }

    /**
     * User ids whose LDAP group forbids being promoted to moderator. The legacy
     * logic checked each participant's lazily loaded LDAP properties; this resolves
     * the same information with a single query for the whole room.
     *
     * @return array<int, true>
     */
    private function promotionBlockedUserIds(Rooms $room): array
    {
        $blocked = [];

        $disallowed = $this->themeService->getApplicationProperties('LDAP_DISALLOW_PROMOTE');
        if (!is_array($disallowed)) {
            $decoded = json_decode((string) ($disallowed ?? '[]'), true);
            $disallowed = is_array($decoded) ? $decoded : [];
        }
        if ($disallowed === []) {
            return $blocked;
        }

        $userIds = array_values(array_map(
            static fn (User $user): int => $user->getId(),
            $room->getUser()->toArray()
        ));
        if ($userIds === []) {
            return $blocked;
        }

        foreach ($this->em->getRepository(LdapUserProperties::class)->findBy(['user' => $userIds]) as $ldapProperties) {
            $ldapUser = $ldapProperties->getUser();
            if ($ldapUser !== null && in_array($ldapProperties->getLdapNumber(), $disallowed, true)) {
                $blocked[$ldapUser->getId()] = true;
            }
        }

        return $blocked;
    }

    /**
     * All user facing strings of the participant management modal, translated
     * server-side (same catalogue as the legacy twig modal).
     *
     * @return array<string, string>
     */
    private function buildTranslations(): array
    {
        $t = $this->translator;

        return [
            'close' => $t->trans('Schließen'),
            'organizer' => $t->trans('Organisator'),
            'invite' => $t->trans('Einladen'),
            'invited' => $t->trans('Eingeladen'),
            'waitinglist' => $t->trans('Warteliste'),
            'searchPlaceholder' => $t->trans('input.placeholder.search'),
            'typeToSearch' => $t->trans('Tippen zum Suchen'),
            'bulkInvite' => $t->trans('Mehrere E-Mail-Adressen auf einmal eingeben'),
            'bulkInviteLabel' => $t->trans('label.teilnehmerEmailhinzufuegen', [], 'form'),
            'bulkInviteHelp' => $t->trans('help.emailTextfeld', [], 'form'),
            'bulkInviteSubmit' => $t->trans('label.teilnehmerSpeichern', [], 'form'),
            'confirmTitle' => $t->trans('Bestätigung'),
            'confirmOk' => $t->trans('OK'),
            'confirmCancel' => $t->trans('Abbrechen'),
            'errorTitle' => $t->trans('Fehler'),
            'errorDefault' => $t->trans('Fehler'),
            'loadFailed' => $t->trans('Beim Laden ist ein Fehler aufgetreten.'),
            'sipNumber' => $t->trans('email.sip.number'),
            'sipRoomNumber' => $t->trans('email.sip.roomnumber'),
            'sipPin' => $t->trans('email.sip.pin'),
        ];
    }

    private function url(string $route, array $params = []): string
    {
        return $this->urlGenerator->generate($route, $params);
    }

    private function env(string $key): string
    {
        return (string) ($_SERVER[$key] ?? $_ENV[$key] ?? getenv($key) ?: '');
    }
}
