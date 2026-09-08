import { postJson, requestJson } from './client';
import type { PastRoomsPage, RoomStatus, ToggleFavoriteResponse } from '../types';

export function fetchOccupantStatus(url: string, ids: number[]): Promise<RoomStatus> {
    if (!ids || ids.length === 0) {
        return Promise.resolve({
            now: Math.floor(Date.now() / 1000),
            open: {},
            closed: {},
            hasStatus: {},
            occupants: {},
        });
    }
    const separator = url.includes('?') ? '&' : '?';
    return requestJson<RoomStatus>(`${url}${separator}ids=${encodeURIComponent(ids.join(','))}`, {
        cache: 'no-store',
    });
}

export function fetchPastRooms(url: string, offset: number): Promise<PastRoomsPage> {
    const separator = url.includes('?') ? '&' : '?';
    return requestJson<PastRoomsPage>(`${url}${separator}offset=${encodeURIComponent(offset)}`);
}

export function toggleFavorite(url: string, uid: string): Promise<ToggleFavoriteResponse> {
    return postJson<ToggleFavoriteResponse>(url, { uid });
}
