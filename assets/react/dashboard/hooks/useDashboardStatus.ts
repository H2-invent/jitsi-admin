import { useEffect, useRef, useState } from 'react';
import type { RoomStatus } from '../types';

const POLL_INTERVAL_MS = 5000;

function mapsEqual<V>(a: Record<string, V> | undefined, b: Record<string, V> | undefined): boolean {
    if (a === b) {
        return true;
    }
    const aKeys = Object.keys(a || {});
    const bKeys = Object.keys(b || {});
    if (aKeys.length !== bKeys.length) {
        return false;
    }
    for (const key of aKeys) {
        const av = a ? a[key] : undefined;
        const bv = b ? b[key] : undefined;
        if (av === bv) {
            continue;
        }
        if (Array.isArray(av) && Array.isArray(bv)) {
            if (av.length !== bv.length) {
                return false;
            }
            for (let i = 0; i < av.length; i++) {
                if (av[i] !== bv[i]) {
                    return false;
                }
            }
            continue;
        }
        return false;
    }
    return true;
}

function mergeMap<V>(prevMap: Record<string, V> | undefined, nextMap: Record<string, V> | undefined): Record<string, V> {
    return mapsEqual(prevMap, nextMap) ? prevMap || {} : { ...(nextMap || {}) };
}

function mergeStatus(prev: RoomStatus, next: RoomStatus): RoomStatus {
    const open = mergeMap(prev.open, next.open);
    const closed = mergeMap(prev.closed, next.closed);
    const hasStatus = mergeMap(prev.hasStatus, next.hasStatus);
    const occupants = mergeMap(prev.occupants, next.occupants);

    return {
        now: next.now != null ? next.now : prev.now,
        open,
        closed,
        hasStatus,
        occupants,
    };
}

export default function useDashboardStatus(url: string | null, roomIds: number[], initialStatus: RoomStatus): RoomStatus {
    const [status, setStatus] = useState<RoomStatus>(initialStatus);
    const inFlight = useRef(false);
    const controllerRef = useRef<AbortController | null>(null);
    const idsKey = roomIds.join(',');
    const idsRef = useRef(idsKey);
    idsRef.current = idsKey;

    useEffect(() => {
        let cancelled = false;
        const tick = async () => {
            if (cancelled || inFlight.current || (typeof document !== 'undefined' && document.hidden)) {
                return;
            }
            const ids = idsRef.current ? idsRef.current.split(',').filter(Boolean).map(Number) : [];
            if (ids.length === 0) {
                return;
            }
            const baseUrl = url;
            if (!baseUrl) {
                return;
            }
            inFlight.current = true;
            const controller = new AbortController();
            controllerRef.current = controller;
            try {
                const separator = baseUrl.includes('?') ? '&' : '?';
                const response = await fetch(
                    `${baseUrl}${separator}ids=${encodeURIComponent(ids.join(','))}`,
                    {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                        signal: controller.signal,
                    }
                );
                if (!response.ok) {
                    throw new Error(`occupants request failed (${response.status})`);
                }
                const payload = (await response.json()) as RoomStatus;
                if (!cancelled) {
                    setStatus((prev) => mergeStatus(prev, payload));
                }
            } catch (e) {
                // keep previous room data; the next polling cycle recovers
            } finally {
                inFlight.current = false;
                if (controllerRef.current === controller) {
                    controllerRef.current = null;
                }
            }
        };

        const handleVisibility = () => {
            if (!document.hidden) {
                tick();
            }
        };
        const handleRefreshEvent = () => {
            tick();
        };
        document.addEventListener('visibilitychange', handleVisibility);
        window.addEventListener('dashboard:refresh', handleRefreshEvent);
        const timer = window.setInterval(tick, POLL_INTERVAL_MS);
        return () => {
            cancelled = true;
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', handleVisibility);
            window.removeEventListener('dashboard:refresh', handleRefreshEvent);
            if (controllerRef.current) {
                controllerRef.current.abort();
                controllerRef.current = null;
            }
        };
    }, [url]);

    return status;
}
