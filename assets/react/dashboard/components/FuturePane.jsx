import React, { useEffect, useMemo, useState } from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import RoomCard from './RoomCard';

function EmptyStateCard({ html }) {
    return (
        <div className="card card-body mb-3">
            <p className="text-center mb-0" dangerouslySetInnerHTML={{ __html: html }} />
        </div>
    );
}

const NO_NAMES = [];

function LiveRoomCard({ room, live, ...rest }) {
    const l = live || { running: false, almost: false, minutes: 0 };
    return (
        <RoomCard
            room={room}
            running={l.running}
            almost={l.almost}
            minutes={l.minutes}
            {...rest}
        />
    );
}

export default function FuturePane({ rooms, status, liveById, favoriteIds, favoritePending, onVisibleIdsChange }) {
    const { config } = React.useContext(DashboardConfigContext);
    const tr = config.translations;

    // The occupant status request must only cover the future conferences that are
    // actually near the current viewpoint (3 cards above / below), not the whole
    // dashboard. We watch the rendered future cards with a single IntersectionObserver
    // and report the currently visible ids (with a generous rootMargin) to the parent,
    // which feeds exactly these ids into the polling endpoint.
    const allFutureIdsKey = useMemo(() => {
        const ids = [];
        (rooms.scheduled || []).forEach((r) => r && r.id != null && ids.push(r.id));
        (rooms.future || []).forEach((group) => (group.rooms || []).forEach((r) => r && r.id != null && ids.push(r.id)));
        return ids.join(',');
    }, [rooms]);

    const [visibleIds, setVisibleIds] = useState(() => new Set());

    useEffect(() => {
        const container = document.getElementById('ex1-tabs-1');
        if (!container) {
            return undefined;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                setVisibleIds((prev) => {
                    let changed = false;
                    const next = new Set(prev);
                    entries.forEach((entry) => {
                        const id = Number(entry.target.getAttribute('data-room-id'));
                        if (Number.isNaN(id)) {
                            return;
                        }
                        if (entry.isIntersecting) {
                            if (!next.has(id)) {
                                next.add(id);
                                changed = true;
                            }
                        } else if (next.delete(id)) {
                            changed = true;
                        }
                    });
                    return changed ? next : prev;
                });
            },
            { root: null, rootMargin: '700px 0px 700px 0px', threshold: 0 }
        );
        const elements = container.querySelectorAll('[data-room-id]');
        elements.forEach((el) => observer.observe(el));
        return () => observer.disconnect();
    }, [allFutureIdsKey]);

    useEffect(() => {
        if (onVisibleIdsChange) {
            onVisibleIdsChange(Array.from(visibleIds));
        }
    }, [visibleIds, onVisibleIdsChange]);

    const common = {
        open: (id) => Boolean(status.open[id]),
        closed: (id) => Boolean(status.closed[id]),
        occupants: (id) => (status.occupants[id] ? status.occupants[id] : NO_NAMES),
    };

    return (
        <>
            {rooms.scheduled.length > 0 && (
                <>
                    <h4 style={{ paddingTop: '16px' }} className="h4-responsive pl-xl-3">
                        {tr.findAppointment}
                    </h4>
                    {rooms.scheduled.map((room) => (
                        <LiveRoomCard
                            key={room.id}
                            room={room}
                            live={liveById[room.id]}
                            open={common.open(room.id)}
                            closed={common.closed(room.id)}
                            occupantNames={common.occupants(room.id)}
                            isFavorite={favoriteIds.has(room.id)}
                            favoritePending={favoritePending === room.id}
                        />
                    ))}
                </>
            )}

            {rooms.futureEmpty ? (
                <EmptyStateCard html={tr.noConference} />
            ) : rooms.todayEmpty ? (
                <>
                    <h4 style={{ paddingTop: '16px' }} className="h4-responsive pl-xl-3">
                        {tr.today}
                    </h4>
                    <div className="card card-body mb-3">
                        <p className="text-center mb-0">{tr.noConferenceToday}</p>
                    </div>
                </>
            ) : null}

            {rooms.future.map((group) => (
                <React.Fragment key={group.header.type + ':' + group.header.label}>
                    <h4 style={{ paddingTop: '16px' }} className="day h5-responsive pl-xl-3">
                        {group.header.label}
                    </h4>
                    {group.rooms.map((room) => (
                        <LiveRoomCard
                            key={room.id}
                            room={room}
                            live={liveById[room.id]}
                            open={common.open(room.id)}
                            closed={common.closed(room.id)}
                            occupantNames={common.occupants(room.id)}
                            isFavorite={favoriteIds.has(room.id)}
                            favoritePending={favoritePending === room.id}
                        />
                    ))}
                </React.Fragment>
            ))}
        </>
    );
}
