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

/**
 * Removes orphaned modal backdrops and the body scroll lock that Bootstrap/MDB
 * left behind when a modal is closed or unmounted while a transition was still
 * running. A stray `.modal-backdrop` covers the whole viewport with a solid gray
 * layer (see _join.scss) and blocks all clicks, so this must never be left behind.
 */
export function cleanupOrphanedModalBackdrops(): void {
    const visibleModals = document.querySelectorAll('.modal.show').length;
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > visibleModals) {
        for (let i = visibleModals; i < backdrops.length; i++) {
            backdrops[i].remove();
        }
    }
    if (visibleModals === 0) {
        document.body.classList.remove('modal-open');
    }
}
