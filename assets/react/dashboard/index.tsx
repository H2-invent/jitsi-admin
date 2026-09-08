import React from 'react';
import { createRoot } from 'react-dom/client';
import DashboardPage from './DashboardPage';
import AddressBook from './addressbook/AddressBook';
import type { AddressBookState, DashboardInitialState } from './types';

const stateNode = document.getElementById('dashboard-state');
const rootNode = document.getElementById('dashboard-root');

const addressBookStateNode = document.getElementById('addressbook-state');
const addressBookRootNode = document.getElementById('addressbook-root');

function parseState<T>(node: Element | null): T | null {
    if (!node) {
        return null;
    }
    try {
        return JSON.parse(node.textContent || 'null') as T;
    } catch (e) {
        console.error('Bootstrap state could not be parsed', e);
        return null;
    }
}

function mount(): void {
    if (rootNode) {
        const initialState = parseState<DashboardInitialState>(stateNode);
        const root = createRoot(rootNode);
        root.render(<DashboardPage initialState={initialState} />);
    }
    if (addressBookRootNode) {
        const initialState = parseState<AddressBookState>(addressBookStateNode);
        const root = createRoot(addressBookRootNode);
        root.render(<AddressBook initialState={initialState} />);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
