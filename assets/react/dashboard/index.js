import React from 'react';
import { createRoot } from 'react-dom/client';
import DashboardPage from './DashboardPage';

const stateNode = document.getElementById('dashboard-state');
const rootNode = document.getElementById('dashboard-root');

function mount() {
    if (!rootNode) {
        return;
    }
    let initialState = null;
    if (stateNode) {
        try {
            initialState = JSON.parse(stateNode.textContent || 'null');
        } catch (e) {
            console.error('Dashboard bootstrap state could not be parsed', e);
        }
    }
    const root = createRoot(rootNode);
    root.render(<DashboardPage initialState={initialState} />);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
