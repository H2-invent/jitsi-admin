/**
 * MDB widget initialisation helpers for the React dashboard.
 *
 * mdb-ui-kit scans the DOM only when initMDB / getOrCreateInstance is called, so
 * every time React appends new nodes (lazy loaded rooms, favourite toggles, ...) the
 * affected components must be (re)initialised. getOrCreateInstance is idempotent, so
 * running this on already-initialised nodes is harmless.
 *
 * The components are read from `window.mdb` (exposed by the app entry point) instead of
 * importing mdb-ui-kit in this bundle: importing it a second time would duplicate the
 * document-level data-api handlers (e.g. dropdown toggling), causing widgets to open
 * and immediately close again.
 */
function mdb() {
    if (typeof window === 'undefined' || !window.mdb) {
        return null;
    }
    return window.mdb;
}

export function initMdbComponents(container) {
    if (!container) {
        return;
    }
    const components = mdb();
    if (!components) {
        return;
    }
    container.querySelectorAll('[data-mdb-dropdown-init]').forEach((el) => {
        components.Dropdown.getOrCreateInstance(el);
    });
    container.querySelectorAll('[data-mdb-popover-init]').forEach((el) => {
        components.Popover.getOrCreateInstance(el);
    });
}

/**
 * Re-creates one popover whose content attributes changed (e.g. the occupant name
 * list changed during polling). MDB reads title/content at instance creation time.
 */
export function refreshPopover(node) {
    const components = mdb();
    if (!components || !node) {
        return;
    }
    const instance = components.Popover.getInstance(node);
    if (instance) {
        instance.dispose();
    }
    components.Popover.getOrCreateInstance(node);
}
