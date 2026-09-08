function mdb(): Window['mdb'] | null {
    if (typeof window === 'undefined' || !window.mdb) {
        return null;
    }
    return window.mdb;
}

export function initMdbComponents(container: Element | null): void {
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

export function refreshPopover(node: Element | null): void {
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
