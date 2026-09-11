import { requestJson } from '../api/client';
import type { AddressBookContact } from '../types';

export interface ContactActionResult {
    ok?: boolean;
    error?: string;
    contact?: AddressBookContact;
}

export function postContactAction(url: string): Promise<ContactActionResult> {
    return requestJson<ContactActionResult>(url, { method: 'POST' });
}

export function addContact(url: string, email: string): Promise<ContactActionResult> {
    const body = new FormData();
    body.append('email', email);
    return requestJson<ContactActionResult>(url, { method: 'POST', body });
}
