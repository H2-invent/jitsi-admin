import { postJson, requestJson, type AbortablePromise } from './client';
import type {
    ActionResult,
    AddParticipantsResponse,
    ParticipantSearchResponse,
    ParticipantsState,
} from '../types';

export function fetchParticipantsState(url: string): Promise<ParticipantsState> {
    return requestJson<ParticipantsState>(url, { cache: 'no-store' });
}

export function searchParticipants(
    url: string,
    term: string
): AbortablePromise<ParticipantSearchResponse> {
    const separator = url.includes('?') ? '&' : '?';
    return requestJson<ParticipantSearchResponse>(
        `${url}${separator}search=${encodeURIComponent(term)}`,
        { cache: 'no-store' }
    );
}

export function addParticipants(url: string, participants: string[]): Promise<AddParticipantsResponse> {
    return postJson<AddParticipantsResponse>(url, { participant: participants });
}

export async function bulkAddParticipants(url: string, member: string): Promise<ActionResult> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ member }),
        cache: 'no-store',
    });
    return responseToActionResult(response);
}

async function responseToActionResult(response: Response): Promise<ActionResult> {
    let payload: Record<string, unknown> | null = null;
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        try {
            payload = (await response.json()) as Record<string, unknown>;
        } catch (e) {
            payload = null;
        }
    }

    const data = payload || {};
    if (typeof data.error === 'boolean') {
        return {
            ok: !data.error,
            message: typeof data.message === 'string' ? data.message : null,
            color: typeof data.color === 'string' ? data.color : null,
            payload: data,
        };
    }
    if (data.ok === true) {
        return { ok: true, payload: data };
    }
    if (typeof data.snack === 'string') {
        return { ok: false, message: data.snack, color: 'danger', payload: data };
    }
    if (typeof data.error === 'string') {
        return { ok: false, message: data.error, color: 'danger', payload: data };
    }
    if (!response.ok) {
        return {
            ok: false,
            message: typeof data.error === 'string' ? data.error : null,
            color: 'danger',
            payload: data,
        };
    }
    return { ok: true, payload: data };
}

/**
 * Runs one of the participant action endpoints (moderator toggle, delete, resend,
 * waiting list accept, ...). The endpoints return JSON for requests that send an
 * `Accept: application/json` header; a non-JSON answer is treated as a failure.
 */
export async function runParticipantAction(url: string): Promise<ActionResult> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
        cache: 'no-store',
    });
    return responseToActionResult(response);
}
