<?php

declare(strict_types=1);

namespace App\Service\Dashboard;

use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\ExternalApplication;
use App\Repository\RoomsRepository;
use App\Service\DashboardService;
use App\Service\FormatName;
use App\Service\Jigasi\JigasiService;
use App\Service\ParticipantSearchService;
use App\Service\ServerUserManagment;
use App\Service\Theme\ThemeService;
use App\Service\webhook\RoomStatusFrontendService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Parsedown;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the structured JSON view-model for the React dashboard.
 *
 * All business rules stay here / on the server: the React application only receives
 * pre-computed capabilities, urls, labels and values and renders them.
 */
class DashboardViewService
{
    private const SETTINGS_FROM_ENV = [
        'input_settings_allow_sheduling',
        'input_settings_allow_roomPlanning',
        'dropdown_settings_common_share_links',
        'dropdown_settings_common_delete',
        'dropdown_settings_common_edit',
        'dropdown_settings_common_duplicate',
        'dropdown_settings_series_edit_one',
        'dropdown_settings_series_edit_all',
        'dropdown_settings_series_type',
        'dropdown_settings_series_delete',
        'dropdown_settings_series_delete_one',
        'dropdown_settings_mail_to_all',
        'dropdown_settings_series_new',
        'dropdown_settings_shedule_planer',
        'dropdown_settings_shedule_share_links',
        'dropdown_settings_shedule_delete',
        'dropdown_settings_shedule_edit',
        'dropdown_settings_shedule_mail_to_all',
        'dropdown_settings_generate_report',
    ];

    public function __construct(
        private readonly RoomsRepository $roomsRepository,
        private readonly DashboardService $dashboardService,
        private readonly RoomStatusFrontendService $roomStatusFrontendService,
        private readonly ThemeService $themeService,
        private readonly EntityManagerInterface $em,
        private readonly ServerUserManagment $serverUserManagment,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FormatName $formatName,
        private readonly ExternalApplication $externalApplication,
        private readonly ParticipantSearchService $participantSearchService,
        private readonly JigasiService $jigasiService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly Parsedown $parsedown,
    ) {
    }

    /**
     * Builds the complete initial dashboard state for one user.
     */
    public function buildInitialState(User $user): array
    {
        $settings = $this->buildSettings();
        $em = $this->em;
        $repo = $this->roomsRepository;

        $allRooms = $repo->findRoomsForDashboard($user);
        [
            'roomsFuture'     => $roomsFuture,
            'roomsNow'        => $roomsNow,
            'roomsToday'      => $roomsToday,
            'persistantRooms' => $persistantRooms,
            'scheduledRooms'  => $scheduledRooms,
            'roomIds'         => $roomIds,
        ] = $this->dashboardService->categorizeRooms($allRooms, $user);

        $roomsPast = $repo->findRoomsInPast($user, 0);
        foreach ($roomsPast as $room) {
            $roomIds[] = $room->getId();
        }

        $favorites = $repo->findFavoriteRooms($user);
        foreach ($favorites as $room) {
            $roomIds[] = $room->getId();
        }

        $uniqueRoomIds = array_unique($roomIds);
        $maps = $this->statusMaps($uniqueRoomIds);

        $allDisplayedRooms = array_merge($allRooms, $roomsPast, $favorites);
        $closedForStartMap = $this->dashboardService->getRoomClosedForStartMap(
            $allDisplayedRooms,
            $user,
            $maps['open']
        );

        $schedulingVotes = $em->getRepository(\App\Entity\SchedulingTimeUser::class)->findVotesForUserAndRooms(
            $user,
            $uniqueRoomIds
        );

        $runningRoomIds = array_map(static fn(Rooms $r) => $r->getId(), $roomsNow);
        $favoriteRoomIds = array_map(static fn(Rooms $r) => $r->getId(), $favorites);

        $ctx = [
            'user' => $user,
            'settings' => $settings,
            'maps' => $maps,
            'closedForStart' => $closedForStartMap,
            'votes' => $schedulingVotes,
            'runningRoomIds' => $runningRoomIds,
            'favoriteRoomIds' => $favoriteRoomIds,
            'nowTs' => time(),
            'theme' => $this->themeService->getTheme(),
        ];

        $futureGroups = [];
        foreach ($roomsFuture as $date => $rooms) {
            $futureGroups[] = [
                'header' => $this->buildDateHeader($rooms[0], $user, $date),
                'rooms' => array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $rooms),
            ];
        }

        $pastOffset = 1;

        return [
            'config' => $this->buildConfig($user, $settings),
            'rooms' => [
                'scheduled' => array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $scheduledRooms),
                'future' => $futureGroups,
                'futureEmpty' => count($roomsFuture) === 0,
                'todayEmpty' => count($roomsToday) === 0,
                'past' => [
                    'rooms' => array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $roomsPast),
                    'hasMore' => count($roomsPast) > 0,
                    'nextOffset' => $pastOffset,
                ],
                'fixed' => array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $persistantRooms),
            ],
            'favorites' => array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $favorites),
            'status' => [
                'now' => $ctx['nowTs'],
                'open' => $maps['open'],
                'closed' => $maps['closed'],
                'hasStatus' => $maps['hasStatus'],
                'occupants' => $maps['occupants'],
            ],
        ];
    }

    public function buildPastPage(User $user, int $offset): array
    {
        $rooms = $this->roomsRepository->findRoomsInPast($user, $offset);
        $settings = $this->buildSettings();
        $ids = array_map(static fn(Rooms $r) => $r->getId(), $rooms);
        $maps = $this->statusMaps($ids);
        $ctx = [
            'user' => $user,
            'settings' => $settings,
            'maps' => $maps,
            'closedForStart' => [],
            'votes' => [],
            'runningRoomIds' => [],
            'favoriteRoomIds' => [],
            'nowTs' => time(),
            'theme' => $this->themeService->getTheme(),
        ];

        return [
            'rooms' => array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $rooms),
            'hasMore' => count($rooms) > 0,
            'nextOffset' => $offset + 1,
        ];
    }

    public function buildStatusResponse(User $user, array $roomIds): array
    {
        $visible = $this->roomsRepository->findVisibleRoomsByIds($user, $roomIds);
        $visibleIds = array_map(static fn(Rooms $r) => $r->getId(), $visible);
        $maps = $this->statusMaps($visibleIds);

        return [
            'now' => time(),
            'open' => $maps['open'],
            'closed' => $maps['closed'],
            'hasStatus' => $maps['hasStatus'],
            'occupants' => $maps['occupants'],
        ];
    }

    public function buildFavoriteRooms(User $user): array
    {
        $favorites = $this->roomsRepository->findFavoriteRooms($user);
        $settings = $this->buildSettings();
        $ids = array_map(static fn(Rooms $r) => $r->getId(), $favorites);
        $maps = $this->statusMaps($ids);
        $ctx = [
            'user' => $user,
            'settings' => $settings,
            'maps' => $maps,
            'closedForStart' => [],
            'votes' => [],
            'runningRoomIds' => [],
            'favoriteRoomIds' => $ids,
            'nowTs' => time(),
            'theme' => $this->themeService->getTheme(),
        ];

        return array_map(fn(Rooms $r) => $this->buildRoom($r, $ctx), $favorites);
    }

    private function statusMaps(array $roomIds): array
    {
        return [
            'open' => $this->roomStatusFrontendService->getRoomCreatedStatusMap($roomIds),
            'closed' => $this->roomStatusFrontendService->getRoomClosedStatusMap($roomIds),
            'hasStatus' => $this->roomStatusFrontendService->getRoomHasStatusMap($roomIds),
            'occupants' => $this->roomStatusFrontendService->getRoomOccupantsMap($roomIds),
        ];
    }

    private function buildConfig(User $user, array $settings): array
    {
        $theme = $this->themeService->getTheme();
        $request = $this->requestStack->getCurrentRequest();

        return [
            'locale' => $request?->getLocale() ?? $user->getLocale() ?? 'de',
            'timezone' => $user->getTimeZone() ?? 'UTC',
            'userId' => $user->getId(),
            'userUid' => $user->getUid(),
            'showTimeZoneSwitch' => (int) ($settings['allowTimeZoneSwitch'] ?? 0) === 1,
            'useMultiframe' => (int) $settings['useMultiframe'] === 1,
            'showSipRoomNumber' => (int) $settings['showSipRoomNumber'] === 1,
            'showNameFrontend' => $settings['laf_showNameFrontend'] ?? '$user.username$',
            'serverCount' => $settings['serverCount'],
            'urls' => [
                'dashboard' => $this->urlGenerator->generate('dashboard'),
                'roomNew' => $this->urlGenerator->generate('room_new'),
                'occupants' => $this->urlGenerator->generate('dashboard_api_occupants'),
                'pastRooms' => $this->urlGenerator->generate('dashboard_api_rooms_past'),
                'favoriteToggle' => $this->urlGenerator->generate('dashboard_api_favorite_toggle'),
            ],
            'themeColors' => $theme && is_array($theme) ? [
                'badgeModerator' => $theme['colorBadgeModerator'] ?? null,
                'badgeSchedule' => $theme['colorBadgeShedule'] ?? null,
                'badgeInternal' => $theme['colorBadgeInternal'] ?? null,
                'badgeSeries' => $theme['colorBadgeSeries'] ?? null,
            ] : null,
            'translations' => $this->buildTranslations(),
        ];
    }

    /**
     * All user facing dashboard strings, translated server-side. Strings that contain
     * a live value ({number}, {time}) keep the placeholder so the client can substitute
     * the current value without duplicating the translation catalogue.
     */
    private function buildTranslations(): array
    {
        $t = $this->translator;
        $sub = static fn(string $translated): string => str_replace('{number}', '{{number}}', $translated);
        $roomNewUrl = $this->url('room_new');

        return [
            'sidebarTitle' => $t->trans('favorite.sidebar.title'),
            'sidebarHelp' => $t->trans('favorite.sidebar.help'),
            'tabFuture' => $t->trans('Zukünftige Konferenzen'),
            'tabFixed' => $t->trans('Feste Konferenzen'),
            'tabPast' => $t->trans('Vergangene Konferenzen'),
            'findAppointment' => $t->trans('Einen Termin finden'),
            'today' => $t->trans('Heute'),
            'tomorrow' => $t->trans('Morgen'),
            'noConference' => $t->trans('dashboard.noconference', ['{url}' => $roomNewUrl]),
            'noConferenceToday' => $t->trans('Heute steht keine Jitsi Meet Konferenz an.'),
            'noPastConferences' => $t->trans('Aktuell sind keine vergangenen Konferenzen vorhanden.'),
            'loadFailed' => $t->trans('Beim Laden ist ein Fehler aufgetreten.'),
            'retry' => $t->trans('Erneut versuchen'),
            'inConferenceNumber' => $sub($t->trans('status.inconference.number')),
            'inConference' => $t->trans('status.inconference'),
            'finished' => $t->trans('status.finished'),
            'organizer' => $t->trans('Organisator'),
            'schedulePlanner' => $t->trans('Terminplaner'),
            'internal' => $t->trans('Intern'),
            'series' => $t->trans('Serie'),
            'privateConference' => $t->trans('Private Konferenz'),
            'plannedBy' => $t->trans('Geplant von'),
            'createdBy' => $t->trans('room.creator.text'),
            'server' => $t->trans('Server'),
            'participants' => $t->trans('Anzahl Eingeladene'),
            'createdInTimezone' => $t->trans('Erstellt in Zeitzone'),
            'agenda' => $t->trans('Agenda'),
            'noAgenda' => $t->trans('Keine Angabe'),
            'options' => $t->trans('Optionen'),
            'manageParticipants' => $t->trans('Teilnehmer verwalten'),
            'edit' => $t->trans('Bearbeiten'),
            'pdfDownload' => $t->trans('PDF Download'),
            'recordings' => $t->trans('Aufnahmen'),
            'transcripts' => $t->trans('Transkripte'),
            'duplicate' => $t->trans('Duplizieren'),
            'newSeriesAppointment' => $t->trans('Neue Serien Termin'),
            'inviteLinks' => $t->trans('Einladungslinks'),
            'reportItem' => $t->trans('report.dropdown.item'),
            'mailToParticipants' => $t->trans('Mail an die Teilnehmer'),
            'sendProtocol' => $t->trans('room.option.sendProtokoll.button'),
            'sendProtocolQuestion' => $t->trans('room.option.sendProtokoll.question'),
            'delete' => $t->trans('Löschen'),
            'deleteSeries' => $t->trans('Serie löschen'),
            'editSeriesType' => $t->trans('Serienart bearbeiten'),
            'editSingleSeries' => $t->trans('Einzelnes Serienelement bearbeiten'),
            'editAllSeries' => $t->trans('Alle Serienelemente bearbeiten'),
            'joinLink' => $t->trans('Beitretenlink'),
            'start' => $t->trans('Starten'),
            'scheduling' => $t->trans('Terminplanung'),
            'transformScheduler' => $t->trans('scheduler.transform'),
            'whiteboard' => $t->trans('options.whiteboard'),
            'meetingNotes' => $t->trans('options.meetingNotes'),
            'lookylooky' => $t->trans('options.lookylooky'),
            'confirmDeleteRoom' => $t->trans('confirm.delete.room'),
            'confirmDeleteSeries' => $t->trans('confirm.delete.series'),
            'startingInMinutes' => $sub($t->trans('Startet in {time} min', ['{time}' => '{{time}}'])),
            'now' => $t->trans('Jetzt'),
            'fixedRoom' => $t->trans('fixed.Room.name'),
            'schedulePlanning' => $t->trans('Terminplanung'),
        ];
    }

    private function buildSettings(): array
    {
        $settings = [];
        foreach (self::SETTINGS_FROM_ENV as $key) {
            $settings[$key] = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key) ?: null;
        }

        $app = fn(string $key) => $this->themeService->getApplicationProperties($key);

        $settings['allowTimeZoneSwitch'] = $app('allowTimeZoneSwitch');
        $settings['useMultiframe'] = $app('LAF_USE_MULTIFRAME');
        $settings['showSipRoomNumber'] = $app('SIP_SHOW_ROOMNUMBER_IN_DETAILS');
        $settings['showParticipantsForParticipants'] = $app('LAF_SHOW_PARTICIPANTS_ON_PARTICIPANTS');
        $settings['whiteboardFunction'] = $app('LAF_WHITEBOARD_FUNCTION');
        $settings['etherpadFunction'] = $app('LAF_ETHERPAD_FUNCTION');
        $settings['lookylookyFunction'] = $app('LAF_LOOKYLOOKY_FUNCTION');
        $settings['lookylookyUrl'] = $app('LOOKYLOOKY_URL');
        $settings['downloadPdf'] = $app('DROPDOWN_SETTINGS_DOWNLOAD_PDF');
        $settings['sendProtokoll'] = $app('DROPDOWN_SETTINGS_SEND_PROTOCOLL');
        $settings['laf_showNameFrontend'] = $app('laf_showNameFrontend');

        $user = $this->requestStack->getCurrentRequest()?->getUser();
        $settings['serverCount'] = $user instanceof User
            ? count($this->serverUserManagment->getServersFromUser($user))
            : 0;

        return $settings;
    }

    private function buildDateHeader(Rooms $room, User $user, int|string $date): array
    {
        $date = (string) $date;
        $tz = new \DateTimeZone($user->getTimeZone() ?? 'UTC');
        $today = (new \DateTimeImmutable('now', $tz));
        $tomorrow = $today->modify('+1 day');
        $start = $room->getStartwithTimeZone($user);
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'de';

        if ($start) {
            $startTz = \DateTimeImmutable::createFromInterface($start);
            if ($startTz->format('Y-m-d') === $today->format('Y-m-d')) {
                return ['type' => 'today', 'label' => $this->translator->trans('Heute')];
            }
            if ($startTz->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                return ['type' => 'tomorrow', 'label' => $this->translator->trans('Morgen')];
            }

            $weekday = $this->intlFormat($startTz, 'EEEE', $locale);
            $longDate = $this->intlFormat($startTz, null, $locale, \IntlDateFormatter::LONG);
            return ['type' => 'date', 'label' => trim($weekday . ', ' . $longDate, ', ')];
        }

        return ['type' => 'date', 'label' => $date];
    }

    private function intlFormat(\DateTimeInterface $date, ?string $pattern, string $locale, int $dateType = \IntlDateFormatter::NONE): string
    {
        try {
            if ($pattern !== null) {
                $fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $date->getTimezone(), null, $pattern);
            } else {
                $fmt = new \IntlDateFormatter($locale, $dateType, \IntlDateFormatter::NONE, $date->getTimezone());
            }
            $res = $fmt->format($date);
            return $res === false ? '' : $res;
        } catch (\Exception $e) {
            return '';
        }
    }

    public function buildRoom(Rooms $room, array $ctx): array
    {
        /** @var User $user */
        $user = $ctx['user'];
        $s = $ctx['settings'];
        $maps = $ctx['maps'];
        $now = $ctx['nowTs'];

        $id = $room->getId();
        $readOnly = UtilsHelper::isRoomReadOnly($room, $user);
        $canOrganize = !$readOnly && UtilsHelper::isAllowedToOrganizeRoom($user, $room);
        $moderator = $room->getModerator();
        $creator = $room->getCreator();
        $userIsModerator = $moderator !== null && $moderator === $user;
        $moderatorNotCreator = $moderator !== null && $creator !== null && $moderator !== $creator;
        $isFavorite = in_array($room, $ctx['favoriteRoomIds'] ?? [], true) || $room->getFavoriteUsers()->contains($user);
        $isPersistent = (bool) $room->getPersistantRoom();
        $isSchedule = (bool) $room->getScheduleMeeting();
        $hasTime = !$isSchedule && !$isPersistent;
        $startTz = null;
        if ($room->getStart() !== null) {
            $startTz = $room->getStartwithTimeZone($user);
        }
        $endTz = null;
        if ($room->getEnddate() !== null) {
            $endTz = $room->getEndwithTimeZone($user);
        }

        $name = ($userIsModerator && $room->getSecondaryName() !== null)
            ? $room->getSecondaryName()
            : $room->getName();

        $moderatorName = $moderator
            ? $this->formatName->formatName($s['laf_showNameFrontend'] ?? '$user.username$', $moderator)
            : null;
        $creatorName = $creator
            ? $this->formatName->formatName($s['laf_showNameFrontend'] ?? '$user.username$', $creator)
            : null;

        $participantsCount = count($room->getUser());
        if ($room->getPublic() && $room->getMaxParticipants() !== null) {
            $participantsText = $this->translator->trans('{from} von {to}', [
                '{from}' => $participantsCount,
                '{to}' => $room->getMaxParticipants(),
            ]);
        } else {
            $participantsText = (string) $participantsCount;
        }

        $server = $room->getServer();
        $showServer = ($s['serverCount'] ?? 0) > 1 && $server !== null;

        $showTimezone = (int) ($s['allowTimeZoneSwitch'] ?? 0) === 1 && !$isPersistent;

        $tag = $room->getTag();

        $roomNumberPopover = null;
        if ((int) ($s['showSipRoomNumber'] ?? 0) === 1 && $room->getCallerRoom()) {
            $numbers = $this->jigasiService->getNumber($room);
            if (is_array($numbers) && count($numbers) > 0) {
                $html = '<p>' . $this->translator->trans('email.sip.pin') . ': '
                    . chunk_split((string) $room->getCallerRoom()->getCallerId(), 3, ' ') . '#</p>';
                foreach ($numbers as $key => $numberSet) {
                    if (!is_array($numberSet)) {
                        continue;
                    }
                    foreach ($numberSet as $number) {
                        $html .= '<p> (' . htmlspecialchars((string) $key, ENT_QUOTES) . ') ' . htmlspecialchars((string) $number, ENT_QUOTES) . ' </p><br>';
                    }
                }
                $roomNumberPopover = [
                    'title' => $this->translator->trans('email.sip.number'),
                    'content' => $html,
                ];
            }
        }

        $agenda = $room->getAgenda();
        $agendaPopover = [
            'title' => $this->translator->trans('Agenda'),
            'content' => $agenda !== null
                ? $this->markdownToHtml($agenda)
                : $this->translator->trans('Keine Angabe'),
        ];

        $options = [];
        $icons = [];
        $leaveAction = null;
        $participantsManageButton = null;
        $shareLinkButton = null;

        if (!$readOnly) {
            if ($canOrganize) {
                $options[] = $this->anchor('participants', $this->t('manageParticipants'), $this->url('room_add_user', ['room' => $id]), 'fa-solid fa-users', ['loadContent']);

                if ($isSchedule) {
                    $options = array_merge($options, $this->scheduleOptionItems($room, $id, $s, $ctx));
                } elseif ($room->getRepeater()) {
                    $options = array_merge($options, $this->repeaterOptionItems($room, $id, $s, $ctx, $now));
                } else {
                    $options = array_merge($options, $this->commonOptionItems($room, $id, $s, $ctx, $now, $canOrganize));
                }

                if ($room->getTotalOpenRooms()) {
                    $shareLinkButton = $this->anchor(
                        'shareLink',
                        $this->t('joinLink'),
                        $this->url('room_enter_link', ['uid' => $room->getUid()]),
                        'fa fa-link',
                        ['loadContent']
                    );
                } else {
                    $participantsManageButton = $this->anchor('participants', null, $this->url('room_add_user', ['room' => $id]), 'fa-solid fa-users', ['loadContent']);
                }
            } else {
                $leaveAction = $this->anchor(
                    'leave',
                    null,
                    $this->url('room_user_remove', ['room' => $id, 'user' => $user->getId()]),
                    'fa-solid fa-trash',
                    ['directSendWithConfirm', 'btn-darkred'],
                    null,
                    null,
                    $this->t('confirmDeleteRoom')
                );
                $icons = $this->participantIconItems($room, $id, $s, $ctx, $canOrganize);
            }
        }

        $startInfo = null;
        $scheduleInfo = null;
        $schedulePublicUrl = null;
        if ($isSchedule) {
            $schedule = $room->getSchedulings()->first();
            if ($schedule) {
                $schedulePublicUrl = $this->url('schedule_public_main', [
                    'scheduleId' => $schedule->getUid(),
                    'userId' => $user->getUid(),
                ]);
            }
        }
        if (!$readOnly && !$canOrganize && $room->getUser()->contains($user)) {
            if ($isSchedule) {
                $hasVoted = isset($ctx['votes'][$id]);
                if ($hasVoted) {
                    $scheduleInfo = [
                        'url' => $this->url('schedule_admin_select_best', ['id' => $id]),
                        'label' => $this->t('transformScheduler'),
                        'loadContent' => true,
                        'icon' => 'fa-regular fa-calendar-check',
                    ];
                } else {
                    $scheduleInfo = [
                        'url' => $schedulePublicUrl,
                        'label' => $this->t('scheduling'),
                        'target' => '_blank',
                        'icon' => null,
                    ];
                }
            } elseif (!$readOnly) {
                $toast = null;
                if (isset($ctx['closedForStart'][$id])) {
                    $toast = $ctx['closedForStart'][$id];
                }
                $startInfo = [
                    'url' => $this->url('room_join', ['t' => 'b', 'room' => $id]),
                    'iframe' => (int) ($s['useMultiframe'] ?? 0) === 1,
                    'roomName' => $room->getName(),
                    'iframeToast' => $toast,
                ];
            }
        }

        $roomModel = [
            'id' => $id,
            'uid' => $room->getUid(),
            'uidReal' => $room->getUidReal(),
            'name' => $name,
            'isFavorite' => $isFavorite,
            'favoriteUrl' => $this->url('room_favorite_toogle', ['uid' => $room->getUidReal()]),
            'readOnly' => $readOnly,
            'canOrganize' => $canOrganize,
            'isPersistent' => $isPersistent,
            'isSchedule' => $isSchedule,
            'isRepeater' => $room->getRepeater() !== null,
            'isInternal' => (bool) $room->getOnlyRegisteredUsers(),
            'isPublic' => (bool) $room->getPublic(),
            'totalOpenRooms' => (bool) $room->getTotalOpenRooms(),
            'hasLobby' => (bool) $room->getLobby(),
            'hasTime' => $hasTime,
            'start' => $startTz ? [
                'ts' => $room->getStartTimestamp(),
                'time' => $startTz->format('H:i'),
                'dateTime' => $startTz->format('d.m.Y H:i'),
                'date' => $startTz->format('d.m.Y'),
            ] : null,
            'end' => $endTz ? ['time' => $endTz->format('H:i')] : null,
            'tag' => $tag ? [
                'title' => $tag->getTitle(),
                'color' => $tag->getColor(),
                'backgroundColor' => $tag->getBackgroundColor(),
            ] : null,
            'userTimezone' => $user->getTimeZone(),
            'timeZoneAuto' => $room->getTimeZoneAuto(),
            'showTimezone' => $showTimezone,
            'moderatorName' => $moderatorName,
            'creatorName' => $creatorName,
            'showCreator' => $canOrganize && $creator !== null && $moderatorNotCreator,
            'moderatorNotCreator' => $moderatorNotCreator,
            'userIsModerator' => $userIsModerator,
            'userInRoom' => $room->getUser()->contains($user),
            'serverName' => $showServer ? $server?->getServerName() : null,
            'participantsText' => $participantsText,
            'agenda' => $agendaPopover,
            'sip' => $roomNumberPopover,
            'hasRecordings' => count($room->getUploadedRecordings()) > 0,
            'hasTranscriptions' => count($room->getTranscriptions()) > 0,
            'changelogUrl' => ($canOrganize && $userIsModerator && $moderatorNotCreator)
                ? $this->url('app_change_log', ['room_id' => $id])
                : null,
            'scheduleAdminUrl' => ($canOrganize && $isSchedule)
                ? $this->url('schedule_admin', ['id' => $id])
                : null,
            'schedulePublicUrl' => $schedulePublicUrl,
            'joinUrl' => $this->url('room_join', ['t' => 'b', 'room' => $id]),
            'pastParticipantsUrl' => ($canOrganize && !(bool) $room->getTotalOpenRooms())
                ? $this->url('room_past_user', ['room' => $id])
                : null,
            'actions' => [
                'optionItems' => $options,
                'icons' => $icons,
                'leave' => $leaveAction,
                'participantsManage' => $participantsManageButton,
                'shareLink' => $shareLinkButton,
                'start' => $startInfo,
                'schedule' => $scheduleInfo,
            ],
            'badgeStyle' => $ctx['theme'] ? [
                'moderator' => $ctx['theme']['colorBadgeModerator'] ?? null,
                'schedule' => $ctx['theme']['colorBadgeShedule'] ?? null,
                'internal' => $ctx['theme']['colorBadgeInternal'] ?? null,
                'series' => $ctx['theme']['colorBadgeSeries'] ?? null,
            ] : null,
        ];

        if (isset($ctx['runningRoomIds'])) {
            $roomModel['isRunning'] = in_array($id, $ctx['runningRoomIds'], true);
        }

        return $roomModel;
    }

    private function t(string $translationKey): string
    {
        return $this->translator->trans($translationKey);
    }

    private function url(string $route, array $params = []): string
    {
        return $this->urlGenerator->generate($route, $params);
    }

    private function markdownToHtml(string $markdown): string
    {
        try {
            return $this->parsedown->text($markdown);
        } catch (\Exception $e) {
            $this->logger->warning('Could not render agenda markdown: ' . $e->getMessage());
            return htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');
        }
    }

    private function anchor(
        string $key,
        ?string $label,
        string $href,
        string $icon,
        array $classes = [],
        ?string $target = null,
        ?array $data = null,
        ?string $confirmText = null,
        bool $disabled = false
    ): array {
        $item = [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'classes' => $classes,
        ];
        if ($target !== null) {
            $item['target'] = $target;
        }
        if ($data !== null) {
            $item['data'] = $data;
        }
        if ($confirmText !== null) {
            $item['confirmText'] = $confirmText;
        }
        if ($disabled) {
            $item['disabled'] = true;
        }
        return $item;
    }

    private function extraAppsOptionItems(Rooms $room, array $s): array
    {
        $items = [];
        $server = $room->getServer();
        if ((int) ($s['whiteboardFunction'] ?? 0) === 1 && $server && $server->isDisableWhiteboard() !== true) {
            $items[] = $this->anchor(
                'whiteboard',
                $this->t('whiteboard'),
                $this->externalApplication->whitebophirLink($room, true),
                'fa-solid fa-chalkboard',
                ['startIframe', 'apps-dropdown-whiteboard'],
                null,
                ['roomname' => $room->getName(), 'close' => 'simple']
            );
        }
        if ((int) ($s['etherpadFunction'] ?? 0) === 1 && $server && $server->isDisableEtherpad() !== true) {
            $user = $this->requestStack->getCurrentRequest()?->getUser();
            $name = $user instanceof User ? $this->participantSearchService->buildShowInFrontendStringNoString($user) : null;
            $items[] = $this->anchor(
                'etherpad',
                $this->t('meetingNotes'),
                $this->etherpadLinkSafely($room, $name),
                'fa-solid fa-clipboard',
                ['startIframe', 'apps-dropdown-etherpad'],
                null,
                ['roomname' => $room->getName(), 'close' => 'simple']
            );
        }
        if ((int) ($s['lookylookyFunction'] ?? 0) === 1 && $server && $server->isDisableEtherpad() !== true) {
            $items[] = $this->anchor(
                'lookylooky',
                $this->t('lookylooky'),
                (string) ($s['lookylookyUrl'] ?? ''),
                'fa-solid fa-file-pdf',
                ['startIframe', 'apps-dropdown-etherpad'],
                null,
                ['roomname' => 'Looky Looky', 'close' => 'simple']
            );
        }
        return $items;
    }

    private function etherpadLinkSafely(Rooms $room, ?string $name): string
    {
        try {
            return $this->externalApplication->etherpadLink($room, $name);
        } catch (\Exception $e) {
            return $this->externalApplication->etherpadLink($room);
        }
    }

    private function commonOptionItems(Rooms $room, int $id, array $s, array $ctx, int $now, bool $canOrganize): array
    {
        $items = [];
        $isPersistent = (bool) $room->getPersistantRoom();
        $endTs = $room->getEndTimestamp();
        $hasStatus = isset($ctx['maps']['hasStatus'][$id]);

        if ((int) ($s['dropdown_settings_common_edit'] ?? 0) === 1 && ($isPersistent || !$endTs || $endTs > $now)) {
            $items[] = $this->anchor('edit', $this->t('edit'), $this->url('room_new', ['id' => $id]), 'fa fa-edit', ['loadContent']);
        }
        $items = array_merge($items, $this->extraAppsOptionItems($room, $s));
        if ((int) ($s['downloadPdf'] ?? 0) === 1) {
            $items[] = $this->anchor('pdf', $this->t('pdfDownload'), $this->url('app_download_sumary', ['room' => $id]), 'fa-solid fa-file-pdf', [], '_blank');
        }
        if (count($room->getUploadedRecordings()) > 0) {
            $items[] = $this->anchor('recordings', $this->t('recordings'), $this->url('recording_modal', ['room' => $id]), 'fa-solid fa-film', ['loadContent']);
        }
        if (count($room->getTranscriptions()) > 0) {
            $items[] = $this->anchor('transcripts', $this->t('transcripts'), $this->url('app_transcription_modal', ['room' => $id]), 'fa-solid fa-file-lines', ['loadContent']);
        }
        if ((int) ($s['dropdown_settings_common_duplicate'] ?? 0) === 1) {
            $items[] = $this->anchor('duplicate', $this->t('duplicate'), $this->url('room_clone', ['room' => $id]), 'fa fa-copy', ['loadContent']);
        }
        if (!$isPersistent && !(bool) $room->getTotalOpenRooms() && (int) ($s['dropdown_settings_series_new'] ?? 0) === 1) {
            $items[] = $this->anchor('newSeries', $this->t('newSeriesAppointment'), $this->url('repeater_new', ['room' => $id]), 'fa fa-repeat', ['loadContent']);
        }
        if ((bool) $room->getPublic() && !(bool) $room->getTotalOpenRooms() && (int) ($s['dropdown_settings_common_share_links'] ?? 0) === 1) {
            $items[] = $this->anchor('shareLinks', $this->t('inviteLinks'), $this->url('share_link', ['id' => $id]), 'fa-solid fa-link', ['loadContent']);
        }
        if ((int) ($s['dropdown_settings_generate_report'] ?? 0) === 1) {
            $items[] = $this->anchor(
                'report',
                $this->t('reportItem'),
                $this->url('app_report_create', ['id' => $id]),
                'fa-solid fa-timeline',
                $hasStatus ? ['loadContent'] : ['loadContent', 'disabled'],
                null,
                null,
                null,
                !$hasStatus
            );
        }
        if ((int) ($s['dropdown_settings_mail_to_all'] ?? 0) === 1) {
            $items[] = $this->anchor('mailAll', $this->t('mailToParticipants'), $this->mailToHref($room), 'fa fa-envelope');
        }
        if ((int) ($s['sendProtokoll'] ?? 0) === 1) {
            $items[] = $this->anchor(
                'sendProtocol',
                $this->t('sendProtocol'),
                $this->url('app_send_summary', ['id' => $id]),
                'fas fa-solid fa-paper-plane',
                ['confirmHref'],
                null,
                null,
                $this->t('sendProtocolQuestion')
            );
        }
        if ((int) ($s['dropdown_settings_common_delete'] ?? 0) === 1) {
            $items[] = $this->deleteItem($room, $id, $now);
        }
        return $items;
    }

    private function mailToHref(Rooms $room): string
    {
        $bcc = [];
        foreach ($room->getUser() as $u) {
            if ($u->getEmail()) {
                $bcc[] = $u->getEmail();
            }
        }
        return 'mailto:?bcc=' . implode(';', $bcc);
    }

    private function repeaterOptionItems(Rooms $room, int $id, array $s, array $ctx, int $now): array
    {
        $items = [];
        $repeater = $room->getRepeater();
        $endTs = $room->getEndTimestamp();
        $hasStatus = isset($ctx['maps']['hasStatus'][$id]);

        if ($repeater && (int) ($s['dropdown_settings_series_type'] ?? 0) === 1) {
            $items[] = $this->anchor('seriesType', $this->t('editSeriesType'), $this->url('repeater_edit_repeater', ['repeat' => $repeater->getId()]), 'fa fa-edit fa-repeat', ['loadContent']);
        }
        if ((int) ($s['dropdown_settings_series_edit_one'] ?? 0) === 1 && $endTs && $endTs > $now) {
            $items[] = $this->anchor('seriesOne', $this->t('editSingleSeries'), $this->url('repeater_edit_room', ['id' => $id, 'type' => 'single']), 'fa fa-edit', ['loadContent']);
        }
        if ((int) ($s['dropdown_settings_series_edit_all'] ?? 0) === 1) {
            $items[] = $this->anchor('seriesAll', $this->t('editAllSeries'), $this->url('repeater_edit_room', ['id' => $id, 'type' => 'all']), 'fa fa-edit fa-repeat', ['loadContent']);
        }
        $items = array_merge($items, $this->extraAppsOptionItems($room, $s));
        if ((int) ($s['downloadPdf'] ?? 0) === 1) {
            $items[] = $this->anchor('pdf', $this->t('pdfDownload'), $this->url('app_download_sumary', ['room' => $id]), 'fa-solid fa-file-pdf', [], '_blank');
        }
        if ((int) ($s['dropdown_settings_mail_to_all'] ?? 0) === 1) {
            $items[] = $this->anchor('mailAll', $this->t('mailToParticipants'), $this->mailToHref($room), 'fa fa-envelope');
        }
        if ((int) ($s['dropdown_settings_generate_report'] ?? 0) === 1) {
            $items[] = $this->anchor(
                'report',
                $this->t('reportItem'),
                $this->url('app_report_create', ['id' => $id]),
                'fa-solid fa-timeline',
                $hasStatus ? ['loadContent'] : ['loadContent', 'disabled'],
                null,
                null,
                null,
                !$hasStatus
            );
        }
        if (count($room->getTranscriptions()) > 0) {
            $items[] = $this->anchor('transcripts', $this->t('transcripts'), $this->url('app_transcription_modal', ['room' => $id]), 'fa-solid fa-file-lines', ['loadContent']);
        }
        if ((int) ($s['sendProtokoll'] ?? 0) === 1) {
            $items[] = $this->anchor(
                'sendProtocol',
                $this->t('sendProtocol'),
                $this->url('app_send_summary', ['id' => $id]),
                'fas fa-solid fa-paper-plane',
                ['confirmHref'],
                null,
                null,
                $this->t('sendProtocolQuestion')
            );
        }
        if ((int) ($s['dropdown_settings_series_delete_one'] ?? 0) === 1) {
            $items[] = $this->deleteItem($room, $id, $now);
        }
        if ((int) ($s['dropdown_settings_series_delete'] ?? 0) === 1 && $repeater) {
            $items[] = $this->anchor(
                'seriesDelete',
                $this->t('deleteSeries'),
                $this->url('repeater_remove', ['repeat' => $repeater->getId()]),
                'fa fa-trash fa-repeat',
                ['confirmHref'],
                null,
                null,
                $this->t('confirmDeleteSeries')
            );
        }
        return $items;
    }

    private function scheduleOptionItems(Rooms $room, int $id, array $s, array $ctx): array
    {
        $items = [];
        if ((int) ($s['dropdown_settings_shedule_planer'] ?? 0) === 1) {
            $items[] = $this->anchor('schedulePlanner', $this->t('schedulePlanner'), $this->url('schedule_admin', ['id' => $id]), 'fa-solid fa-calendar', ['loadContent']);
        }
        if ((int) ($s['dropdown_settings_shedule_edit'] ?? 0) === 1) {
            $items[] = $this->anchor('scheduleEdit', $this->t('edit'), $this->url('schedule_admin_new', ['id' => $id]), 'fa fa-edit', ['loadContent']);
        }
        if ((int) ($s['dropdown_settings_shedule_share_links'] ?? 0) === 1
            && (bool) $room->getPublic()
            && !$room->getRepeater()
            && !(bool) $room->getTotalOpenRooms()
        ) {
            $items[] = $this->anchor('scheduleLinks', $this->t('inviteLinks'), $this->url('share_link', ['id' => $id]), 'fa-solid fa-link', ['loadContent']);
        }
        if (count($room->getTranscriptions()) > 0) {
            $items[] = $this->anchor('transcripts', $this->t('transcripts'), $this->url('app_transcription_modal', ['room' => $id]), 'fa-solid fa-file-lines', ['loadContent']);
        }
        $items = array_merge($items, $this->extraAppsOptionItems($room, $s));
        if ((int) ($s['dropdown_settings_shedule_mail_to_all'] ?? 0) === 1) {
            $items[] = $this->anchor('mailAll', $this->t('mailToParticipants'), $this->mailToHref($room), 'fa fa-envelope');
        }
        if ((int) ($s['dropdown_settings_shedule_delete'] ?? 0) === 1) {
            $items[] = $this->anchor(
                'scheduleDelete',
                $this->t('delete'),
                $this->url('room_remove', ['room' => $id]),
                'fa fa-trash',
                ['confirmHref'],
                null,
                null,
                $this->t('confirmDeleteRoom')
            );
        }
        return $items;
    }

    private function deleteItem(Rooms $room, int $id, int $now): array
    {
        if ((bool) $room->getPersistantRoom() || ($room->getEnddate() !== null && $room->getEndTimestamp() > $now)) {
            return $this->anchor(
                'delete',
                $this->t('delete'),
                $this->url('room_remove', ['room' => $id]),
                'fa fa-trash',
                ['confirmHref'],
                null,
                null,
                $this->t('confirmDeleteRoom')
            );
        }
        return $this->anchor(
            'leave',
            $this->t('delete'),
            $this->url('room_user_remove', ['room' => $id, 'user' => $this->requestStack->getCurrentRequest()?->getUser()?->getId()]),
            'fa fa-trash',
            ['confirmHref'],
            null,
            null,
            $this->t('confirmDeleteRoom')
        );
    }

    private function participantIconItems(Rooms $room, int $id, array $s, array $ctx, bool $canOrganize): array
    {
        $items = [];
        $server = $room->getServer();
        if ((int) ($s['showParticipantsForParticipants'] ?? 0) === 1 && !$canOrganize && !(bool) $room->getTotalOpenRooms()) {
            $items[] = $this->anchor('participants', null, $this->url('room_past_user', ['room' => $id]), 'fas fa-users', ['loadContent']);
        }
        if ((int) ($s['whiteboardFunction'] ?? 0) === 1 && !$canOrganize && $server && $server->isDisableWhiteboard() !== true) {
            $items[] = $this->anchor('whiteboard', null, $this->externalApplication->whitebophirLink($room, false), 'fa-solid fa-chalkboard', ['startIframe'], null, ['roomname' => $room->getName(), 'close' => 'simple']);
        }
        if ((int) ($s['etherpadFunction'] ?? 0) === 1 && !$canOrganize && $server && $server->isDisableEtherpad() !== true) {
            $user = $this->requestStack->getCurrentRequest()?->getUser();
            $name = $user instanceof User ? $this->participantSearchService->buildShowInFrontendStringNoString($user) : null;
            $items[] = $this->anchor('etherpad', null, $this->etherpadLinkSafely($room, $name), 'fa-solid fa-clipboard', ['startIframe'], null, ['roomname' => $room->getName(), 'close' => 'simple']);
        }
        return $items;
    }
}
