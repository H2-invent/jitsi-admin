import { Popover } from 'mdb-ui-kit';

let pollUrl = '';
let pollInterval = 5000;
let timer = null;
let inFlight = false;

export function initOccupantPolling(url, interval = 5000) {
    if (!url) {
        return;
    }
    pollUrl = url;
    pollInterval = interval;
    if (timer) {
        clearInterval(timer);
    }
    timer = setInterval(refreshOccupants, pollInterval);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshOccupants();
        }
    });
    refreshOccupants();
}

function refreshOccupants() {
    if (!pollUrl || inFlight || document.visibilityState === 'hidden') {
        return;
    }
    const blocks = getVisibleBlocks();
    if (blocks.length === 0) {
        return;
    }
    const roomIds = [...new Set(blocks.map(b => b.dataset.roomId))].join(',');
    inFlight = true;
    fetch(pollUrl + '?roomIds=' + encodeURIComponent(roomIds))
        .then(response => response.json())
        .then(data => {
            blocks.forEach(block => {
                const entry = data[block.dataset.roomId];
                if (!entry) {
                    return;
                }
                updateBlock(block, entry);
            });
        })
        .catch(() => {})
        .finally(() => {
            inFlight = false;
        });
}

/**
 * Returns the occupant blocks whose room card is currently on screen.
 *
 * Only these rooms are polled. Rooms that are off-screen or inside an inactive
 * tab are skipped, so the request stays small no matter how many rooms have been
 * lazy-loaded into the DOM. The DOM is re-scanned on every poll tick, so cards
 * added by the infinite-scroll lazy loader are picked up too.
 *
 * Note: the dashboard hides inactive tab panes with `transform: translateX(110%)`,
 * not `display:none`, so they still report client rects. We therefore check both
 * the horizontal and the vertical viewport intersection to exclude those cards.
 */
function getVisibleBlocks() {
    return Array.from(document.querySelectorAll('.occupant[data-room-id]')).filter(block => {
        const card = block.closest('.card');
        if (!card) {
            return false;
        }
        const rects = card.getClientRects();
        if (rects.length === 0) {
            // not laid out at all
            return false;
        }
        const rect = rects[0];
        return rect.left < window.innerWidth
            && rect.right > 0
            && rect.top < window.innerHeight
            && rect.bottom > 0;
    });
}

function updateBlock(block, entry) {
    const open = !!entry.open;
    block.classList.toggle('d-none', !open);

    const countEl = block.querySelector('.number small');
    if (countEl && countEl.textContent !== String(entry.count)) {
        countEl.textContent = entry.count;
    }

    const link = block.querySelector('[data-mdb-popover-init]');
    if (link) {
        const content = (entry.names || []).map(name => name + '<br>').join('');
        if (link.getAttribute('data-mdb-content') !== content) {
            link.setAttribute('data-mdb-content', content);
            const instance = Popover.getInstance(link);
            if (instance) {
                instance.dispose();
            }
            Popover.getOrCreateInstance(link);
        }
    }

    const alternate = block.parentNode && block.parentNode.querySelector('.occupant-alternate[data-room-id="' + block.dataset.roomId + '"]');
    if (alternate) {
        alternate.classList.toggle('d-none', open);
    }
}
