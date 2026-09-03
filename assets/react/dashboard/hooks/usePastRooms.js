import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Infinite scrolling for the "past conferences" pane. Appends structured room records
 * fetched from the JSON endpoint, guards against duplicates, serialises concurrent
 * requests and keeps the sentinel observer alive across appends.
 */
export default function usePastRooms({ url, initialRooms, initialHasMore, initialNextOffset, enabled }) {
    const [rooms, setRooms] = useState(initialRooms || []);
    const [hasMore, setHasMore] = useState(Boolean(initialHasMore));
    const [nextOffset, setNextOffset] = useState(initialNextOffset || 1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [sentinel, setSentinel] = useState(null);

    const requestId = useRef(0);
    const loadedPages = useRef(new Set());

    useEffect(() => {
        setRooms(initialRooms || []);
        setHasMore(Boolean(initialHasMore));
        setNextOffset(initialNextOffset || 1);
        setLoading(false);
        setError(null);
        loadedPages.current = new Set();
    }, [initialRooms, initialHasMore, initialNextOffset]);

    const loadMore = useCallback(async () => {
        if (!hasMore || loading || !enabled) {
            return;
        }
        const currentRequest = ++requestId.current;
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(url + (url.includes('?') ? '&' : '?') + 'offset=' + nextOffset, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                throw new Error(`past rooms request failed (${response.status})`);
            }
            const payload = await response.json();
            if (currentRequest !== requestId.current) {
                return;
            }
            const incoming = Array.isArray(payload.rooms) ? payload.rooms : [];
            setRooms((prevRooms) => {
                const known = new Set(prevRooms.map((r) => (r && r.id != null ? r.id : null)));
                const fresh = incoming.filter((room) => room && room.id != null && !known.has(room.id));
                return fresh.length > 0 ? [...prevRooms, ...fresh] : prevRooms;
            });
            setHasMore(Boolean(payload.hasMore));
            if (payload.nextOffset != null) {
                setNextOffset(payload.nextOffset);
            }
        } catch (e) {
            if (currentRequest === requestId.current) {
                setError(e);
            }
        } finally {
            if (currentRequest === requestId.current) {
                setLoading(false);
            }
        }
    }, [hasMore, loading, enabled, url, nextOffset]);

    useEffect(() => {
        if (!sentinel || !enabled) {
            return undefined;
        }
        if (!('IntersectionObserver' in window)) {
            loadMore();
            return undefined;
        }
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    loadMore();
                }
            },
            { root: null, rootMargin: '200px' }
        );
        observer.observe(sentinel);
        return () => observer.disconnect();
    }, [sentinel, enabled, loadMore]);

    const retry = useCallback(() => {
        loadMore();
    }, [loadMore]);

    return {
        rooms,
        hasMore,
        loading,
        error,
        sentinelRef: setSentinel,
        retry,
    };
}
