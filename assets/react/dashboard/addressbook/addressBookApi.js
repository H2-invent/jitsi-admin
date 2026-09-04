import { requestJson } from '../api/client';

export function postContactAction(url) {
    return requestJson(url, { method: 'POST' });
}

export function addContact(url, email) {
    const body = new FormData();
    body.append('email', email);
    return requestJson(url, { method: 'POST', body });
}
