import { postJson, requestJson } from './client';

export function fetchOccupantStatus(url, ids) {
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
    return requestJson(`${url}${separator}ids=${encodeURIComponent(ids.join(','))}`, { cache: 'no-store' });
}

export function fetchPastRooms(url, offset) {
    const separator = url.includes('?') ? '&' : '?';
    return requestJson(`${url}${separator}offset=${encodeURIComponent(offset)}`);
}

export function toggleFavorite(url, uid) {
    return postJson(url, { uid });
}
