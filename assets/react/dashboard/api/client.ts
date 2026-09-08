const CSRF_TOKEN_HEADER = 'X-CSRF-Token';

export interface RequestOptions extends RequestInit {
    headers?: Record<string, string>;
}

interface RequestFailure extends Error {
    status?: number;
    payload?: unknown;
}

export type AbortablePromise<T> = Promise<T> & { abort: () => void };

function requestJson<T>(url: string, options: RequestOptions = {}): AbortablePromise<T> {
    const controller = new AbortController();
    const { signal } = controller;
    const config: RequestOptions = {
        headers: {
            Accept: 'application/json',
            ...(options.headers || {}),
        },
        signal,
        ...options,
    };
    if (options.body && !(options.body instanceof FormData)) {
        const headers = config.headers || (config.headers = {});
        if (!headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
        }
    }

    const promise = fetch(url, config).then(async (response) => {
        let payload = null;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            try {
                payload = await response.json();
            } catch (e) {
                payload = null;
            }
        }
        if (!response.ok) {
            const error = new Error(
                (payload && (payload as { error?: string }).error) || `Request failed with status ${response.status}`
            ) as RequestFailure;
            error.status = response.status;
            error.payload = payload;
            throw error;
        }
        return payload as T;
    }) as AbortablePromise<T>;

    promise.abort = () => controller.abort();
    return promise;
}

function postJson<T>(url: string, body: unknown): AbortablePromise<T> {
    return requestJson<T>(url, {
        method: 'POST',
        body: JSON.stringify(body || {}),
    });
}

export { requestJson, postJson };
