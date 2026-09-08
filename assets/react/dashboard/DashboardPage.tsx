import React, { createContext, useCallback, useEffect, useMemo, useState } from 'react';
import { toggleFavorite } from './api/dashboardApi';
import useDashboardStatus from './hooks/useDashboardStatus';
import FavoriteSidebar from './components/FavoriteSidebar';
import RoomTabs from './components/RoomTabs';
import { almostRunning, isRunning, minutesToStart } from './utils/rooms';
import type { DashboardConfig, DashboardInitialState, LiveRoomInfo, Room, RoomCollection, RoomStatus } from './types';

export interface DashboardConfigContextValue {
    config: DashboardConfig;
    onToggleFavorite: (room: Room) => Promise<void>;
}

export const DashboardConfigContext = createContext<DashboardConfigContextValue | null>(null);

export interface DashboardPageProps {
    initialState: DashboardInitialState | null;
}

export default function DashboardPage({ initialState }: DashboardPageProps) {
    const [favorites, setFavorites] = useState<Room[]>(() => (initialState ? initialState.favorites || [] : []));
    const [favoritePending, setFavoritePending] = useState<number | null>(null);
    const [favoriteError, setFavoriteError] = useState<string | null>(null);
    // The room lists are kept in state so that deleted conferences can be removed
    // from the dashboard without reloading the page.
    const [rooms, setRooms] = useState<RoomCollection | null>(() =>
        initialState ? initialState.rooms || null : null
    );
    // Only the future conferences currently near the viewport are polled for occupant
    // status (see FuturePane). This keeps the occupants request small and targeted.
    const [pollRoomIds, setPollRoomIds] = useState<number[]>([]);

    const config = useMemo<DashboardConfig | null>(() => (initialState ? initialState.config || null : null), [
        initialState,
    ]);

    useEffect(() => {
        function handleRoomRemoved(e: Event) {
            const id = Number((e as CustomEvent<{ id?: unknown }>).detail?.id);
            if (!Number.isInteger(id) || id <= 0) {
                return;
            }
            setRooms((prev) => {
                if (!prev) {
                    return prev;
                }
                const scheduled = (prev.scheduled || []).filter((r) => r && r.id !== id);
                const fixed = (prev.fixed || []).filter((r) => r && r.id !== id);
                const future = (prev.future || [])
                    .map((group) => ({ ...group, rooms: (group.rooms || []).filter((r) => r && r.id !== id) }))
                    .filter((group) => (group.rooms || []).length > 0);
                if (scheduled.length === (prev.scheduled || []).length
                    && fixed.length === (prev.fixed || []).length
                    && future.length === (prev.future || []).length) {
                    return prev;
                }
                return { ...prev, scheduled, future, fixed };
            });
            setFavorites((prev) => prev.filter((r) => r && r.id !== id));
        }
        window.addEventListener('dashboard-room-removed', handleRoomRemoved);
        return () => window.removeEventListener('dashboard-room-removed', handleRoomRemoved);
    }, []);
    const initialStatus = useMemo<RoomStatus>(
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
        const map: Record<number, LiveRoomInfo> = {};
        const compute = (r: Room | null | undefined) => {
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
        async (room: Room) => {
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
                setFavoriteError(e instanceof Error ? e.message : 'Favorite update failed');
            }
        },
        [config, favoritePending]
    );

    const contextValue = useMemo<DashboardConfigContextValue>(
        () => ({ config, onToggleFavorite: handleToggleFavorite }),
        [config, handleToggleFavorite]
    );

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
