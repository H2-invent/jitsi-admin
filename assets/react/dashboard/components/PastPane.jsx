import React, { useContext } from 'react';
import usePastRooms from '../hooks/usePastRooms';
import { DashboardConfigContext } from '../DashboardPage';
import PastRoomCard from './PastRoomCard';

const NO_NAMES = [];

export default function PastPane({ rooms, status, favoriteIds, favoritePending }) {
    const { config } = useContext(DashboardConfigContext);
    const tr = config.translations;
    const past = rooms.past || { rooms: [], hasMore: false, nextOffset: 1 };

    const { rooms: pastRooms, hasMore, loading, error, sentinelRef, retry } = usePastRooms({
        url: config.urls.pastRooms,
        initialRooms: past.rooms,
        initialHasMore: past.hasMore,
        initialNextOffset: past.nextOffset,
        enabled: true,
    });

    if (pastRooms.length === 0 && !hasMore) {
        return (
            <div id="ex1-tabs-2-init">
                <div className="card card-body">
                    <p className="text-center mb-0">{tr.noPastConferences}</p>
                </div>
            </div>
        );
    }

    return (
        <div id="ex1-tabs-2-init">
            {pastRooms.map((room) => (
                <PastRoomCard
                    key={room.id}
                    room={room}
                    open={Boolean(status.open[room.id])}
                    closed={Boolean(status.closed[room.id])}
                    occupantNames={status.occupants[room.id] ? status.occupants[room.id] : NO_NAMES}
                    isFavorite={favoriteIds.has(room.id)}
                    favoritePending={favoritePending === room.id}
                />
            ))}

            {hasMore && (
                <div className="mb-3" ref={sentinelRef}>
                    {loading && (
                        <div className="fa-3x text-center text-black-50">
                            <i className="fas fa-spinner fa-spin" />
                        </div>
                    )}
                    {error && (
                        <div className="text-center">
                            <p className="text-black-50 mb-1">{tr.loadFailed}</p>
                            <button type="button" className="btn btn-outline-primary btn-sm" onClick={retry}>
                                {tr.retry}
                            </button>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
