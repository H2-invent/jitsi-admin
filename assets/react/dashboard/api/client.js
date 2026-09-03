const CSRF_TOKEN_HEADER = 'X-CSRF-Token';

function requestJson(url, options = {}) {
    const controller = new AbortController();
    const { signal } = controller;
    const config = {
        headers: {
            Accept: 'application/json',
            ...(options.headers || {}),
        },
        signal,
        ...options,
    };
    if (options.body && !(options.body instanceof FormData) && !config.headers['Content-Type']) {
        config.headers['Content-Type'] = 'application/json';
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
                (payload && payload.error) || `Request failed with status ${response.status}`
            );
            error.status = response.status;
            error.payload = payload;
            throw error;
        }
        return payload;
    });

    promise.abort = () => controller.abort();
    return promise;
}

function postJson(url, body) {
    return requestJson(url, {
        method: 'POST',
        body: JSON.stringify(body || {}),
    });
}

export { requestJson, postJson };
