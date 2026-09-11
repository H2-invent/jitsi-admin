import React, { useContext, useEffect, useRef } from 'react';
import type { CSSProperties } from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import { refreshPopover } from '../utils/mdb';
import { labelWithNumber } from '../utils/rooms';
import type { DashboardConfig, Room } from '../types';

function escapeHtml(value: string): string {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

interface InfoPopoverProps {
    title: string;
    content: string;
    refreshKey: string;
    children: React.ReactNode;
}

function InfoPopover({ title, content, refreshKey, children }: InfoPopoverProps) {
    const anchorRef = useRef<HTMLAnchorElement>(null);
    useEffect(() => {
        refreshPopover(anchorRef.current);
    }, [refreshKey]);
    return (
        <a
            tabIndex={0}
            ref={anchorRef}
            data-mdb-popover-init
            data-mdb-trigger="focus"
            data-mdb-html="true"
            title={title}
            data-mdb-content={content}
        >
            {children}
        </a>
    );
}

interface OccupantStatusProps {
    open: boolean;
    closed: boolean;
    occupantNames: string[];
}

function OccupantStatus({ open, closed, occupantNames }: OccupantStatusProps) {
    const { config } = useContext(DashboardConfigContext)!;
    const tr = config.translations;
    if (open) {
        const content = occupantNames.map((name) => `${escapeHtml(name)}<br>`).join('');
        return (
            <div className="occupant">
                <div className="number">
                    <small>{labelWithNumber(tr.inConferenceNumber, occupantNames.length)}</small>
                    <InfoPopover title={tr.inConference} content={content} refreshKey={occupantNames.join('|')}>
                        <i className="fa fa-info-circle" />
                    </InfoPopover>
                </div>
                <div className="text">{tr.inConference}</div>
            </div>
        );
    }
    if (closed) {
        return (
            <div className="occupant text-danger">
                <div className="text">{tr.finished}</div>
            </div>
        );
    }
    return null;
}

interface FavoriteStarProps {
    room: Room;
    isFavorite: boolean;
    pending: boolean;
}

function FavoriteStar({ room, isFavorite, pending }: FavoriteStarProps) {
    const { onToggleFavorite } = useContext(DashboardConfigContext)!;
    const handleClick = (e: React.MouseEvent<HTMLAnchorElement>) => {
        e.preventDefault();
        if (!pending) {
            onToggleFavorite(room);
        }
    };
    return (
        <a href={room.favoriteUrl} onClick={handleClick}>
            {pending ? <i className="fas fa-spinner fa-spin" /> : <i className={`${isFavorite ? 'fa' : 'far'} fa-star`} />}
        </a>
    );
}

function themeStyle(config: DashboardConfig, colorKey: string): CSSProperties | undefined {
    return config.themeColors && config.themeColors[colorKey as keyof typeof config.themeColors]
        ? { backgroundColor: config.themeColors[colorKey as keyof typeof config.themeColors] as string }
        : undefined;
}

export interface StatusColumnProps {
    room: Room;
    open: boolean;
    closed: boolean;
    occupantNames: string[];
}

export function StatusColumn({ room, open, closed, occupantNames }: StatusColumnProps) {
    const { config } = useContext(DashboardConfigContext)!;
    const tr = config.translations;

    return (
        <div className="col-md-2">
            {room.tag && (
                <span
                    className="badge badge-danger d-block mb-2"
                    style={{ color: room.tag.color, backgroundColor: room.tag.backgroundColor }}
                >
                    {room.tag.title}
                </span>
            )}
            <OccupantStatus open={open} closed={closed} occupantNames={occupantNames} />
            {room.hasTime && (
                <>
                    <h5 className="h5-responsive">
                        {room.start ? room.start.time : ''}
                        {room.end ? ` – ${room.end.time}` : ''}
                    </h5>
                    {room.showTimezone && (
                        <p className="text-black-50 small">
                            <small>{room.userTimezone}</small>
                        </p>
                    )}
                </>
            )}
            {room.canOrganize && (
                <span className="badge badge-info" style={themeStyle(config, 'badgeModerator')}>
                    {room.hasLobby && <i className="fa-solid fa-shield-halved me-1" />}
                    {room.moderatorNotCreator && (
                        <i
                            className={`fa-solid fa-file-contract me-1 createdByDeputy${room.changelogUrl ? ' loadContent' : ''}`}
                            {...(room.changelogUrl ? { href: room.changelogUrl } : {})}
                        />
                    )}
                    {room.hasRecordings && <i className="loadContent fa-solid fa-film" />}
                    {tr.organizer}
                </span>
            )}
            {room.isRepeater && (
                <span className="badge badge-warning" style={themeStyle(config, 'badgeSeries')}>
                    {tr.series}
                </span>
            )}
        </div>
    );
}

export interface NameColumnProps {
    room: Room;
    isFavorite: boolean;
    favoritePending: boolean;
}

export function NameColumn({ room, isFavorite, favoritePending }: NameColumnProps) {
    const { config } = useContext(DashboardConfigContext)!;
    const tr = config.translations;

    return (
        <div className="col-md-4">
            <h5 className="h5-responsive conference-name">
                {room.name}
                <FavoriteStar room={room} isFavorite={isFavorite} pending={favoritePending} />
                {config.showSipRoomNumber && room.sip && (
                    <InfoPopover title={room.sip.title} content={room.sip.content} refreshKey="sip">
                        <i className="fa fa-phone" />
                    </InfoPopover>
                )}
                <InfoPopover title={room.agenda.title} content={room.agenda.content} refreshKey="agenda">
                    <i className="fa fa-info-circle" />
                </InfoPopover>
            </h5>
            <p className="text-black-50 mb-0 small moderatorText">
                {tr.plannedBy}: {room.moderatorName}
            </p>
            {room.showCreator && (
                <p className="text-black-50 mb-0 small createdFromText">
                    {tr.createdBy}: {room.creatorName}
                </p>
            )}
            {room.serverName && (
                <p className="text-black-50 mb-0 small serverText">
                    {tr.server}: {room.serverName}
                </p>
            )}
            <p className="text-black-50 small mb-0 numerParticipantsText">
                {tr.participants}: {room.participantsText}
            </p>
            {room.showTimezone && (
                <p className="text-black-50 mb-0 small timezoneText">
                    {tr.createdInTimezone}: {room.timeZoneAuto}
                </p>
            )}
        </div>
    );
}

export interface ReadonlyColumnProps {
    room: Room;
    open: boolean;
    closed: boolean;
    occupantNames: string[];
}

export function ReadonlyColumn({ room, open, closed, occupantNames }: ReadonlyColumnProps) {
    const { config } = useContext(DashboardConfigContext)!;
    const tr = config.translations;
    return (
        <>
            <div className="col-md-2">
                <span className="badge badge-danger" style={themeStyle(config, 'badgeSeries')}>
                    {tr.privateConference}
                </span>
                {open && (
                    <div className="occupant">
                        <div className="number">
                            <small>{labelWithNumber(tr.inConferenceNumber, occupantNames.length)}</small>
                        </div>
                        <div className="text">{tr.inConference}</div>
                    </div>
                )}
                {closed && (
                    <div className="occupant text-danger">
                        <div className="text">{tr.finished}</div>
                    </div>
                )}
                {room.hasTime && (
                    <>
                        <h5 className="h5-responsive">
                            {room.start ? room.start.time : ''}
                            {room.end ? ` – ${room.end.time}` : ''}
                        </h5>
                        {room.showTimezone && (
                            <p className="text-black-50 small">
                                <small>{room.userTimezone}</small>
                            </p>
                        )}
                    </>
                )}
            </div>
            <div className="col-md-4">
                <h5 className="h5-responsive conference-name">&nbsp;</h5>
                <p className="text-black-50 small">
                    {tr.plannedBy}: {room.moderatorName}
                    <br />
                    {tr.participants}: {room.participantsText}
                    {room.showTimezone && (
                        <>
                            <br />
                            {tr.createdInTimezone}: {room.timeZoneAuto}
                        </>
                    )}
                </p>
            </div>
        </>
    );
}
