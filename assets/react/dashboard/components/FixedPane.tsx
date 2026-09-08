import React from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import RoomCard from './RoomCard';
import type { LiveRoomInfo, RoomCollection, RoomStatus } from '../types';

const NO_NAMES: string[] = [];

export interface FixedPaneProps {
    rooms: RoomCollection;
    status: RoomStatus;
    liveById: Record<number, LiveRoomInfo>;
    favoriteIds: Set<number>;
    favoritePending: number | null;
}

export default function FixedPane({ rooms, status, liveById, favoriteIds, favoritePending }: FixedPaneProps) {
    const { config } = React.useContext(DashboardConfigContext)!;
    const tr = config.translations;

    if (rooms.fixed.length === 0) {
        return (
            <div className="card card-body mb-3">
                <p className="text-center mb-0" dangerouslySetInnerHTML={{ __html: tr.noConference }} />
            </div>
        );
    }

    return (
        <>
            {rooms.fixed.map((room) => {
                const live = liveById[room.id] || { running: false, almost: false, minutes: 0 };
                return (
                    <RoomCard
                        key={room.id}
                        room={room}
                        open={Boolean(status.open[room.id])}
                        closed={Boolean(status.closed[room.id])}
                        occupantNames={status.occupants[room.id] ? status.occupants[room.id] : NO_NAMES}
                        isFavorite={favoriteIds.has(room.id)}
                        favoritePending={favoritePending === room.id}
                        running={live.running}
                        almost={live.almost}
                        minutes={live.minutes}
                    />
                );
            })}
        </>
    );
}
