/**
 * Legacy entry point used by the lobby notification pipeline when a websocket
 * "refreshDashboard" message arrives. The dashboard is now a React application and
 * performs its own occupant/status polling, so this module only forwards the signal;
 * the React dashboard listens for the "dashboard:refresh" event and immediately polls
 * the status endpoint instead of reloading the whole page.
 */
function initRefreshDashboard() {
    // no-op: the React dashboard owns the refresh cadence
}

function refreshDashboard() {
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('dashboard:refresh'));
    }
}

export { initRefreshDashboard, refreshDashboard };
