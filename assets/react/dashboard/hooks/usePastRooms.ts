import { useCallback, useEffect, useRef, useState } from 'react';
import type { PastRoomsPage, Room } from '../types';

export interface UsePastRoomsOptions {
    url: string;
    initialRooms: Room[] | null | undefined;
    initialHasMore: boolean | null | undefined;
    initialNextOffset: number | null | undefined;
    enabled: boolean;
}

export default function usePastRooms({ url, initialRooms, initialHasMore, initialNextOffset, enabled }: UsePastRoomsOptions) {
    const [rooms, setRooms] = useState<Room[]>(initialRooms || []);
    const [hasMore, setHasMore] = useState(Boolean(initialHasMore));
    const [nextOffset, setNextOffset] = useState(initialNextOffset || 1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<Error | null>(null);
    const [sentinel, setSentinel] = useState<HTMLElement | null>(null);

    const requestId = useRef(0);
    const loadedPages = useRef(new Set<number>());

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
            const payload = (await response.json()) as PastRoomsPage;
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
                setError(e as Error);
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

    const removeRoom = useCallback((id: number) => {
        setRooms((prevRooms) => prevRooms.filter((room) => room && room.id !== id));
    }, []);

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
        removeRoom,
    };
}
