import { useEffect, useRef, useState } from 'react';

const POLL_INTERVAL_MS = 5000;

function mapsEqual(a, b) {
    if (a === b) {
        return true;
    }
    const aKeys = Object.keys(a || {});
    const bKeys = Object.keys(b || {});
    if (aKeys.length !== bKeys.length) {
        return false;
    }
    for (const key of aKeys) {
        const av = a[key];
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

function mergeMap(prevMap, nextMap) {
    return mapsEqual(prevMap || {}, nextMap || {}) ? prevMap || {} : { ...(nextMap || {}) };
}

function mergeStatus(prev, next) {
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

/**
 * Polls /room/dashboard/api/occupants for the currently displayed room ids.
 * - stops when the dashboard unmounts
 * - never overlaps requests
 * - pauses while the document is hidden
 * - on failure keeps the previous data untouched (next tick recovers)
 * - only creates new map references for entries that actually changed
 */
export default function useDashboardStatus(url, roomIds, initialStatus) {
    const [status, setStatus] = useState(initialStatus);
    const inFlight = useRef(false);
    const controllerRef = useRef(null);
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
            inFlight.current = true;
            const controller = new AbortController();
            controllerRef.current = controller;
            try {
                const separator = url.includes('?') ? '&' : '?';
                const response = await fetch(
                    `${url}${separator}ids=${encodeURIComponent(ids.join(','))}`,
                    {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                        signal: controller.signal,
                    }
                );
                if (!response.ok) {
                    throw new Error(`occupants request failed (${response.status})`);
                }
                const payload = await response.json();
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
