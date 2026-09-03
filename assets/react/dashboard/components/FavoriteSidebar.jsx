import React, { useMemo } from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import FavoriteRoomCard from './FavoriteRoomCard';

const NO_NAMES = [];

export default function FavoriteSidebar({ favorites, favoritePending, favoriteError, liveById, status }) {
    const { config } = React.useContext(DashboardConfigContext);
    const translations = config.translations;

    // Preserve the server ordering: running favourites first, the rest in their
    // original order afterwards.
    const sorted = useMemo(() => {
        const running = [];
        const others = [];
        favorites.forEach((room) => {
            const live = liveById[room.id];
            if (live && live.running) {
                running.push(room);
            } else {
                others.push(room);
            }
        });
        return [...running, ...others];
    }, [favorites, liveById]);

    return (
        <div id="favorite-Container" className="mb-3">
            <h5 className="text-left border-bottom">
                <i className="far fa-star" />
                <b>{translations.sidebarTitle}</b>
            </h5>

            {favoriteError && (
                <div className="alert alert-danger py-2 small">{favoriteError}</div>
            )}

            {sorted.length === 0 ? (
                <p dangerouslySetInnerHTML={{ __html: translations.sidebarHelp }} />
            ) : (
                sorted.map((room) => {
                    const live = liveById[room.id] || { running: false };
                    const occupant = status.occupants[room.id] ? status.occupants[room.id] : NO_NAMES;
                    return (
                        <FavoriteRoomCard
                            key={room.id}
                            room={room}
                            isRunning={live.running}
                            open={Boolean(status.open[room.id])}
                            closed={Boolean(status.closed[room.id])}
                            occupantNames={occupant}
                            pending={favoritePending === room.id}
                        />
                    );
                })
            )}
        </div>
    );
}
