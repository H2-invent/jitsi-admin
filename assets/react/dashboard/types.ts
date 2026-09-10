export type Translations = Record<string, string>;

export interface DashboardConfig {
    locale: string;
    timezone: string;
    userId: number;
    userUid: string;
    showTimeZoneSwitch: boolean;
    useMultiframe: boolean;
    showSipRoomNumber: boolean;
    showNameFrontend: string;
    serverCount: number;
    urls: {
        dashboard: string;
        roomNew: string;
        occupants: string;
        pastRooms: string;
        favoriteToggle: string;
    };
    themeColors: {
        badgeModerator: string | null;
        badgeSchedule: string | null;
        badgeInternal: string | null;
        badgeSeries: string | null;
    } | null;
    translations: Translations;
}

export interface RoomStart {
    ts: number;
    time: string;
    dateTime: string;
    date: string;
}

export interface RoomTag {
    title: string;
    color: string;
    backgroundColor: string;
}

export interface RoomPopover {
    title: string;
    content: string;
}

export interface DashboardActionItem {
    key: string;
    label?: string | null;
    href: string;
    icon?: string | null;
    classes?: string[];
    target?: string | null;
    data?: Record<string, string>;
    confirmText?: string | null;
    disabled?: boolean;
}

export interface RoomStartAction {
    url: string;
    iframe?: boolean;
    roomName?: string;
    iframeToast?: string | null;
}

export interface RoomScheduleAction {
    url: string;
    label?: string | null;
    icon?: string | null;
    target?: string;
    loadContent?: boolean;
}

export interface RoomActions {
    optionItems: DashboardActionItem[];
    icons: DashboardActionItem[];
    leave: DashboardActionItem | null;
    participantsManage: DashboardActionItem | null;
    participantsUrl: string | null;
    shareLink: DashboardActionItem | null;
    start: RoomStartAction | null;
    schedule: RoomScheduleAction | null;
}

export interface Room {
    id: number;
    uid: string;
    uidReal: string;
    name: string;
    isFavorite: boolean;
    favoriteUrl: string;
    readOnly: boolean;
    canOrganize: boolean;
    isPersistent: boolean;
    isSchedule: boolean;
    isRepeater: boolean;
    isInternal: boolean;
    isPublic: boolean;
    totalOpenRooms: boolean;
    hasLobby: boolean;
    hasTime: boolean;
    start: RoomStart | null;
    end: { time: string; ts?: number } | null;
    tag: RoomTag | null;
    userTimezone: string | null;
    timeZoneAuto: string | null;
    showTimezone: boolean;
    moderatorName: string | null;
    creatorName: string | null;
    showCreator: boolean;
    moderatorNotCreator: boolean;
    userIsModerator: boolean;
    userInRoom: boolean;
    serverName: string | null;
    participantsText: string;
    agenda: RoomPopover;
    sip: RoomPopover | null;
    hasRecordings: boolean;
    hasTranscriptions: boolean;
    changelogUrl: string | null;
    scheduleAdminUrl: string | null;
    schedulePublicUrl: string | null;
    joinUrl: string;
    pastParticipantsUrl: string | null;
    actions: RoomActions;
    badgeStyle: {
        moderator: string | null;
        schedule: string | null;
        internal: string | null;
        series: string | null;
    } | null;
    isRunning?: boolean;
}

export interface RoomFutureGroup {
    header: {
        type: string;
        label: string;
    };
    rooms: Room[];
}

export interface PastRoomsPage {
    rooms: Room[];
    hasMore: boolean;
    nextOffset: number;
}

export interface RoomCollection {
    scheduled: Room[];
    future: RoomFutureGroup[];
    futureEmpty: boolean;
    todayEmpty: boolean;
    past: PastRoomsPage;
    fixed: Room[];
}

export interface RoomStatus {
    now: number;
    open: Record<string, boolean>;
    closed: Record<string, boolean>;
    hasStatus: Record<string, boolean>;
    occupants: Record<string, string[]>;
}

export interface DashboardInitialState {
    config?: DashboardConfig | null;
    rooms?: RoomCollection | null;
    favorites?: Room[];
    status?: RoomStatus | null;
}

export interface LiveRoomInfo {
    running: boolean;
    almost: boolean;
    minutes: number;
}

export interface ToggleFavoriteResponse {
    ok: boolean;
    isFavorite: boolean;
    roomId: number;
    favorites: Room[];
}

export interface AddressBookFilter {
    id: string;
    label: string;
    value: string | string[];
}

export interface AddressBookContact {
    id: number;
    uid: string;
    email: string | null;
    username: string | null;
    name: string;
    nameNoIcon: string;
    initial: string;
    indexer: string;
    categories: string[];
    isFavorite: boolean;
    isDeputy: boolean;
    isDeputyFromLdap: boolean;
    canMakeDeputy: boolean;
    canDelete: boolean;
    profilePicture: string | null;
    color: string;
    avatarText: string;
    favoriteUrl: string;
    removeUrl: string;
    deputyUrl: string;
    adhoc: {
        serverName: string;
        url: string;
    }[];
}

export interface AddressBookConfig {
    doAllowUserCreation: boolean;
    trashOnAdressBook: boolean;
    urls: {
        addAjax: string;
    };
    translations: Translations;
}

export interface AddressBookState {
    contacts: AddressBookContact[];
    filters: AddressBookFilter[];
    config: AddressBookConfig;
}

export interface ParticipantPermission {
    moderator: boolean;
    shareDisplay: boolean;
    privateMessage: boolean;
    lobbyModerator: boolean;
}

export interface ParticipantSipInfo {
    numbers: string[];
    roomNumber: string;
    pin: string;
}

export interface ParticipantAction {
    key: string;
    label: string;
    icon: string;
    href?: string;
    type?: 'action' | 'sip';
    active?: boolean;
    tooltip?: string | null;
    confirmText?: string | null;
}

export interface RoomParticipant {
    id: number;
    uid: string;
    name: string;
    username: string | null;
    profilePicture: string | null;
    isCurrentUser: boolean;
    isOrganizer: boolean;
    permissions: ParticipantPermission;
    sip: ParticipantSipInfo | null;
    actions: ParticipantAction[];
}

export interface WaitinglistEntry {
    id: number;
    email: string;
    acceptUrl: string;
}

export interface ParticipantsState {
    roomId: number;
    title: string;
    addUrl: string;
    bulkAddUrl: string;
    searchUrl: string;
    allowBulkInvite: boolean;
    canPrintParticipants: boolean;
    printUrl: string | null;
    organizer: RoomParticipant | null;
    participants: RoomParticipant[];
    waitinglist: WaitinglistEntry[];
    translations: Translations;
}

export interface ParticipantSearchHit {
    name: string;
    nameNoIcon?: string;
    id: string;
    roles?: string[];
    uid?: string;
}

export interface ParticipantSearchGroup {
    name: string;
    user: string[];
}

export interface ParticipantSearchResponse {
    user: ParticipantSearchHit[];
    group: ParticipantSearchGroup[];
}

export interface AddParticipantsResponse {
    error?: boolean;
    validMember?: string[];
    invalidMember?: string[];
}

export interface ActionResult {
    ok: boolean;
    message?: string | null;
    color?: string | null;
    payload?: unknown;
}
