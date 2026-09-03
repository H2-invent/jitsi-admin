import React, { createContext, useCallback, useMemo, useState } from 'react';
import { toggleFavorite } from './api/dashboardApi';
import useDashboardStatus from './hooks/useDashboardStatus';
import FavoriteSidebar from './components/FavoriteSidebar';
import RoomTabs from './components/RoomTabs';
import { almostRunning, isRunning, minutesToStart } from './utils/rooms';

export const DashboardConfigContext = createContext(null);

export default function DashboardPage({ initialState }) {
    const [favorites, setFavorites] = useState(() => (initialState ? initialState.favorites || [] : []));
    const [favoritePending, setFavoritePending] = useState(null);
    const [favoriteError, setFavoriteError] = useState(null);
    // Only the future conferences currently near the viewport are polled for occupant
    // status (see FuturePane). This keeps the occupants request small and targeted.
    const [pollRoomIds, setPollRoomIds] = useState([]);

    const config = useMemo(() => (initialState ? initialState.config : null), [initialState]);
    const rooms = initialState ? initialState.rooms : null;
    const initialStatus = useMemo(
        () =>
            initialState && initialState.status
                ? initialState.status
                : { now: Math.floor(Date.now() / 1000), open: {}, closed: {}, hasStatus: {}, occupants: {} },
        [initialState]
    );

    const status = useDashboardStatus(
        config ? config.urls.occupants : null,
        pollRoomIds,
        initialStatus
    );

    const nowTs = status && status.now != null ? status.now : Math.floor(Date.now() / 1000);

    // Derived per room, computed once per poll tick so that unchanged room cards can be
    // skipped by React.memo (we only pass primitives down).
    const liveById = useMemo(() => {
        const map = {};
        const compute = (r) => {
            if (r && r.id != null) {
                map[r.id] = {
                    running: isRunning(r, nowTs),
                    almost: almostRunning(r, nowTs),
                    minutes: minutesToStart(r, nowTs),
                };
            }
        };
        if (!rooms) {
            return map;
        }
        (rooms.scheduled || []).forEach(compute);
        (rooms.future || []).forEach((group) => (group.rooms || []).forEach(compute));
        (rooms.fixed || []).forEach(compute);
        favorites.forEach(compute);
        return map;
    }, [rooms, favorites, nowTs]);

    if (!initialState || !config || !rooms) {
        return null;
    }

    const favoriteIds = new Set(favorites.map((f) => f.id));

    const handleToggleFavorite = useCallback(
        async (room) => {
            if (favoritePending != null) {
                return;
            }
            setFavoritePending(room.id);
            setFavoriteError(null);
            try {
                const response = await toggleFavorite(config.urls.favoriteToggle, room.uidReal);
                setFavorites(Array.isArray(response.favorites) ? response.favorites : []);
                setFavoritePending(null);
            } catch (e) {
                setFavoritePending(null);
                setFavoriteError(e.message || 'Favorite update failed');
            }
        },
        [config, favoritePending]
    );

    const contextValue = useMemo(() => ({ config, onToggleFavorite: handleToggleFavorite }), [
        config,
        handleToggleFavorite,
    ]);

    return (
        <DashboardConfigContext.Provider value={contextValue}>
            <div className="sidebarToggle d-none">
                <i className="fa fa-times fa-2x" />
            </div>
            <div className="sidebarToggle">
                <i className="far fa-star fa-2x" />
            </div>
            <div className="sidebar" id="sidebar">
                <div className="sidebarContent">
                    <FavoriteSidebar
                        favorites={favorites}
                        favoriteIds={favoriteIds}
                        favoritePending={favoritePending}
                        favoriteError={favoriteError}
                        liveById={liveById}
                        status={status}
                    />
                </div>
            </div>
            <div className="body">
                <RoomTabs
                    rooms={rooms}
                    status={status}
                    liveById={liveById}
                    favoriteIds={favoriteIds}
                    favoritePending={favoritePending}
                    onVisibleIdsChange={setPollRoomIds}
                />
            </div>
        </DashboardConfigContext.Provider>
    );
}
