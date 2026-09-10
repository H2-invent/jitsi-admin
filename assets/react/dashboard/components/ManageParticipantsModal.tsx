import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import Swal from 'sweetalert2';
import { setSnackbar } from '../../../js/myToastr';
import { cleanupOrphanedModalBackdrops } from '../utils/mdb';
import {
    addParticipants,
    bulkAddParticipants,
    fetchParticipantsState,
    runParticipantAction,
    searchParticipants,
} from '../api/participantsApi';
import type {
    ActionResult,
    ParticipantAction,
    ParticipantSearchHit,
    ParticipantSearchResponse,
    ParticipantsState,
    RoomParticipant,
    Translations,
} from '../types';

export interface ManageParticipantsModalProps {
    dataUrl: string;
    roomName?: string;
    onClose: () => void;
}

interface SearchResultsProps {
    results: ParticipantSearchResponse;
    busy: boolean;
    translations: Translations;
    onPickUser: (hit: ParticipantSearchHit) => void;
    onPickGroup: (group: { name: string; user: string[] }) => void;
}

function displayHitName(hit: ParticipantSearchHit): string {
    if (hit.nameNoIcon) {
        return hit.nameNoIcon;
    }
    return (hit.name || '').replace(/<[^>]*>/g, '');
}

function SearchResults({ results, busy, translations, onPickUser, onPickGroup }: SearchResultsProps) {
    const users = results.user || [];
    const groups = results.group || [];
    if (users.length === 0 && groups.length === 0) {
        return (
            <div className="list-group position-absolute w-100 shadow">
                <div className="list-group-item small text-muted">{translations.typeToSearch || 'Tippen zum Suchen'}</div>
            </div>
        );
    }
    return (
        <div className="list-group position-absolute w-100 shadow">
            {users.map((hit) => (
                <button
                    key={`user-${hit.id}`}
                    type="button"
                    className="list-group-item list-group-item-action text-start"
                    disabled={busy}
                    onClick={() => onPickUser(hit)}
                >
                    <i className="fas fa-user me-2 text-primary" />
                    {displayHitName(hit)}
                </button>
            ))}
            {groups.map((group) => (
                <button
                    key={`group-${group.name}`}
                    type="button"
                    className="list-group-item list-group-item-action text-start"
                    disabled={busy}
                    onClick={() => onPickGroup(group)}
                >
                    <i className="fas fa-users me-2 text-primary" />
                    {group.name}
                </button>
            ))}
        </div>
    );
}

interface ParticipantRowProps {
    participant: RoomParticipant;
    organizer?: boolean;
    translations: Translations;
    onAction: (action: ParticipantAction) => void;
}

function ParticipantRow({ participant, organizer, translations, onAction }: ParticipantRowProps) {
    const [expandedSip, setExpandedSip] = useState(false);
    const triggerRef = useRef<HTMLAnchorElement>(null);
    const rowRef = useRef<HTMLLIElement>(null);

    useEffect(() => {
        const trigger = triggerRef.current;
        if (!trigger || !window.mdb) {
            return;
        }
        const instance = window.mdb.Dropdown.getOrCreateInstance(trigger);
        return () => {
            if (window.mdb && window.mdb.Dropdown.getInstance(trigger)) {
                window.mdb.Dropdown.getInstance(trigger)!.dispose();
            }
        };
    }, []);

    useEffect(() => {
        if (!window.mdb || !rowRef.current) {
            return;
        }
        rowRef.current.querySelectorAll('[data-mdb-tooltip-init]').forEach((el) => {
            window.mdb!.Tooltip.getOrCreateInstance(el);
        });
    }, [participant]);

    const handleAction = (action: ParticipantAction) => {
        if (action.type === 'sip') {
            setExpandedSip((prev) => !prev);
            return;
        }
        onAction(action);
    };

    const sip = participant.sip;
    const showOrganizerBadge = organizer === true;
    const deleteAction = participant.actions.find((action) => action.key === 'delete');
    const menuActions = participant.actions.filter((action) => action.key !== 'delete' && action.key !== 'sip');
    const sipActions = participant.actions.filter((action) => action.key === 'sip');

    return (
        <li ref={rowRef} className="d-flex flex-row align-items-center">
            {participant.actions.length > 0 && (
                <div className="dropdown">
                    <a
                        ref={triggerRef}
                        className="dropdown-toggle"
                        href="#"
                        data-mdb-dropdown-init
                        type="button"
                        data-mdb-auto-close="outside"
                        aria-haspopup="true"
                        aria-expanded="false"
                        onClick={(e) => e.preventDefault()}
                    >
                        <i className="fas fa-ellipsis-v" />
                    </a>
                    <div className="dropdown-menu">
                        {menuActions.map((action) => (
                            <a
                                key={action.key}
                                className={`dropdown-item${action.active ? ' bg-success' : ''}`}
                                href={action.href}
                                title={action.tooltip || undefined}
                                data-mdb-tooltip-init={action.tooltip ? '' : undefined}
                                onClick={(e) => {
                                    e.preventDefault();
                                    handleAction(action);
                                }}
                            >
                                {action.icon ? <i className={`${action.icon} me-1`} /> : null}
                                {action.label}
                            </a>
                        ))}
                        {sipActions.map((action) => (
                            <React.Fragment key={action.key}>
                                <a
                                    className="dropdown-item"
                                    href="#"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        handleAction(action);
                                    }}
                                >
                                    <i className={`${action.icon} me-1`} /> {action.label}
                                </a>
                                {expandedSip && sip && (
                                    <div className="dropdown-item text-wrap small">
                                        {sip.numbers.length > 0 && (
                                            <div className="d-flex w-100">
                                                <div className="me-2">{translations.sipNumber}:</div>
                                                <div>
                                                    {sip.numbers.map((number, index) => (
                                                        <div key={index}>{number}</div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                        {translations.sipRoomNumber}: {sip.roomNumber}
                                        <br />
                                        {translations.sipPin}: {sip.pin}
                                    </div>
                                )}
                            </React.Fragment>
                        ))}
                        {!organizer && deleteAction && (
                            <>
                                <div className="dropdown-divider" />
                                <a
                                    className="dropdown-item"
                                    href={deleteAction.href}
                                    onClick={(e) => {
                                        e.preventDefault();
                                        handleAction(deleteAction);
                                    }}
                                >
                                    <i className="fa fa-trash me-1" /> {deleteAction.label}
                                </a>
                            </>
                        )}
                    </div>
                </div>
            )}
            {!organizer && (
                <span
                    className="profilePic"
                    style={{ backgroundImage: `url('${participant.profilePicture || ''}')` }}
                />
            )}
            <span>{participant.name}</span>
            {showOrganizerBadge && (
                <span className="small">&nbsp;({translations.organizer || 'Organisator'})</span>
            )}
            {!organizer && (
                <span className="ms-2">
                    <i className={`fa fa-crown${participant.permissions.moderator ? '' : ' d-none'}`} />
                    <i className={`fas fa-desktop${participant.permissions.shareDisplay ? '' : ' d-none'}`} />
                    <i className={`far fa-comments${participant.permissions.privateMessage ? '' : ' d-none'}`} />
                    <i className={`fas fa-couch${participant.permissions.lobbyModerator ? '' : ' d-none'}`} />
                </span>
            )}
        </li>
    );
}

export default function ManageParticipantsModal({ dataUrl, roomName, onClose }: ManageParticipantsModalProps) {
    const [host] = useState(() => {
        const el = document.createElement('div');
        el.className = 'modal fade';
        el.setAttribute('tabindex', '-1');
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-hidden', 'true');
        document.body.appendChild(el);
        return el;
    });

    const [state, setState] = useState<ParticipantsState | null>(null);
    const [loading, setLoading] = useState(true);
    const [loadError, setLoadError] = useState(false);
    const [busy, setBusy] = useState(false);
    const [term, setTerm] = useState('');
    const [results, setResults] = useState<ParticipantSearchResponse | null>(null);
    const [searching, setSearching] = useState(false);
    const [showBulkInvite, setShowBulkInvite] = useState(false);
    const [bulkMember, setBulkMember] = useState('');
    const [adding, setAdding] = useState(false);

    const onCloseRef = useRef(onClose);
    const listRef = useRef<HTMLDivElement>(null);
    const searchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const searchAbortRef = useRef<{ abort: () => void } | null>(null);
    const busyRef = useRef(false);
    busyRef.current = busy;

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

    useEffect(() => {
        const ModalCtor = window.mdb && window.mdb.Modal;
        let frame = 0;
        let cancelled = false;

        const showModal = (attempt = 0) => {
            if (cancelled) {
                return;
            }
            // React renders the `.modal-dialog` into this host. Only hand the host
            // to MDB once that child is actually present, otherwise the instance's
            // cached `_dialog` stays null and MDB's `_showElement()` throws
            // "Illegal invocation", leaving only a gray backdrop behind.
            if (!host.querySelector('.modal-dialog')) {
                if (attempt < 30) {
                    frame = requestAnimationFrame(() => showModal(attempt + 1));
                }
                return;
            }
            if (!ModalCtor) {
                host.classList.add('show');
                host.style.display = 'block';
                return;
            }
            let instance = ModalCtor.getInstance(host);
            const dialog = instance
                ? (instance as unknown as { _dialog?: Element | null })._dialog
                : null;
            if (instance && !host.contains(dialog || null)) {
                instance.dispose();
                instance = null;
            }
            if (!instance) {
                instance = ModalCtor.getOrCreateInstance(host);
            }
            instance.show();
        };
        frame = requestAnimationFrame(() => showModal());

        const handleHidden = () => onCloseRef.current();
        host.addEventListener('hidden.bs.modal', handleHidden);

        return () => {
            cancelled = true;
            cancelAnimationFrame(frame);
            host.removeEventListener('hidden.bs.modal', handleHidden);
            if (ModalCtor && ModalCtor.getInstance(host)) {
                ModalCtor.getInstance(host)!.dispose();
            }
            host.remove();
            cleanupOrphanedModalBackdrops();
        };
    }, [host]);

    const loadData = useCallback(
        async (initial: boolean) => {
            if (initial) {
                setLoading(true);
            }
            setLoadError(false);
            try {
                const data = await fetchParticipantsState(dataUrl);
                setState(data);
            } catch (e) {
                setLoadError(true);
            } finally {
                setLoading(false);
            }
        },
        [dataUrl]
    );

    useEffect(() => {
        loadData(true).catch(() => {});
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
            if (searchAbortRef.current) {
                searchAbortRef.current.abort();
            }
        };
    }, [loadData]);

    const tr = useMemo<Translations>(
        () => (state ? state.translations : {}),
        [state]
    );

    const hide = useCallback(() => {
        const ModalCtor = window.mdb && window.mdb.Modal;
        const instance = ModalCtor ? ModalCtor.getOrCreateInstance(host) : null;
        if (!instance) {
            host.classList.remove('show');
            host.style.display = 'none';
            onCloseRef.current();
            cleanupOrphanedModalBackdrops();
            return;
        }
        const state = instance as unknown as { _isShown?: boolean; _isTransitioning?: boolean };
        if (state._isTransitioning && state._isShown) {
            // The modal is still animating in; Bootstrap swallows hide() in that
            // state, so defer the close until the show transition completed.
            host.addEventListener('shown.bs.modal', () => instance.hide(), { once: true });
            return;
        }
        instance.hide();
    }, [host]);

    const showError = useCallback(
        (message?: string | null) => {
            Swal.fire({
                title: tr.errorTitle || 'Fehler',
                text: message || tr.errorDefault || 'Fehler',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
            });
        },
        [tr]
    );

    const confirmBeforeAction = useCallback(
        (action: ParticipantAction): Promise<boolean> => {
            if (!action.confirmText) {
                return Promise.resolve(true);
            }
            return Swal.fire({
                title: tr.confirmTitle || 'Bestätigung',
                text: action.confirmText,
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: tr.confirmCancel || 'Abbrechen',
                confirmButtonText: tr.confirmOk || 'OK',
                heightAuto: false,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton: 'btn-outline-primary btn',
                },
            }).then((result) => result.isConfirmed === true);
        },
        [tr]
    );

    const handleAction = useCallback(
        async (action: ParticipantAction) => {
            if (!action.href || busyRef.current) {
                return;
            }
            if (!(await confirmBeforeAction(action))) {
                return;
            }
            setBusy(true);
            try {
                const result = await runParticipantAction(action.href);
                if (!result.ok) {
                    showError(result.message);
                    return;
                }
                if (action.key === 'transfer') {
                    if (result.message) {
                        setSnackbar(result.message, '', result.color || 'success', false, '0x00', 5000);
                    }
                    window.setTimeout(() => window.location.reload(), 1500);
                    return;
                }
                if (result.message) {
                    setSnackbar(result.message, '', result.color || 'success', false, '0x00', 5000);
                }
                await loadData(false);
            } catch (e) {
                showError(undefined);
            } finally {
                setBusy(false);
            }
        },
        [confirmBeforeAction, loadData, showError]
    );

    const handleSearch = useCallback((value: string) => {
        setTerm(value);
    }, []);

    useEffect(() => {
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }
        if (searchAbortRef.current) {
            searchAbortRef.current.abort();
            searchAbortRef.current = null;
        }
        const trimmed = term.trim();
        if (!trimmed || !state) {
            setResults(null);
            setSearching(false);
            return;
        }
        setSearching(true);
        searchTimeoutRef.current = setTimeout(() => {
            const request = searchParticipants(state.searchUrl, trimmed);
            searchAbortRef.current = request;
            request
                .then((data) => {
                    setResults(data);
                    setSearching(false);
                })
                .catch(() => {
                    setSearching(false);
                });
        }, 350);
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, [term, state]);

    const addHit = useCallback(
        async (participants: string[]) => {
            if (!state || adding || busy || participants.length === 0) {
                return;
            }
            setAdding(true);
            try {
                const response = await addParticipants(state.addUrl, participants);
                if (response && response.error === true) {
                    showError(undefined);
                }
            } catch (e) {
                showError(undefined);
            } finally {
                setAdding(false);
            }
            setTerm('');
            setResults(null);
            await loadData(false);
        },
        [adding, busy, loadData, showError, state]
    );

    const handlePickUser = useCallback(
        (hit: ParticipantSearchHit) => addHit([hit.id]),
        [addHit]
    );

    const handlePickGroup = useCallback(
        (group: { name: string; user: string[] }) => addHit(group.user),
        [addHit]
    );

    const handleBulkInvite = useCallback(
        async (e: React.FormEvent<HTMLFormElement>) => {
            e.preventDefault();
            if (!state || busy || !bulkMember.trim()) {
                return;
            }
            setBusy(true);
            try {
                const result: ActionResult = await bulkAddParticipants(state.bulkAddUrl, bulkMember);
                if (!result.ok) {
                    showError(result.message);
                    return;
                }
                setShowBulkInvite(false);
                setBulkMember('');
                if (result.message) {
                    setSnackbar(result.message, '', result.color || 'success', false, '0x00', 5000);
                }
                await loadData(false);
            } catch (err) {
                showError(undefined);
            } finally {
                setBusy(false);
            }
        },
        [bulkMember, busy, loadData, showError, state]
    );

    const acceptWaitinglist = useCallback(
        async (entry: { email: string; acceptUrl: string }) => {
            if (busy) {
                return;
            }
            setBusy(true);
            try {
                const result = await runParticipantAction(entry.acceptUrl);
                if (!result.ok) {
                    showError(result.message);
                    return;
                }
                await loadData(false);
            } catch (e) {
                showError(undefined);
            } finally {
                setBusy(false);
            }
        },
        [busy, loadData, showError]
    );

    const reload = useCallback(() => {
        setLoadError(false);
        loadData(true).catch(() => {});
    }, [loadData]);

    const showSearchResults = results !== null && (results.user.length > 0 || results.group.length > 0);
    const allParticipants = state ? [state.organizer, ...state.participants] : [];

    return createPortal(
        <div className="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-xl" id="atendeeModalScroll">
            <div className="modal-content">
                <div className="modal-header light-blue darken-3 white-text">
                    <h5 className="modal-title">{state ? state.title : roomName || ''}</h5>
                    <button type="button" className="btn-close" aria-label="Close" onClick={hide} />
                </div>
                <div className="modal-body">
                    {loading && !state ? (
                        <div className="text-center py-5">
                            <i className="fas fa-spinner fa-spin fa-2x" />
                        </div>
                    ) : loadError && !state ? (
                        <div className="text-center py-5">
                            <p>{tr.loadFailed || 'Beim Laden ist ein Fehler aufgetreten.'}</p>
                            <button type="button" className="btn btn-primary" onClick={reload}>
                                {tr.confirmOk || 'OK'}
                            </button>
                        </div>
                    ) : (
                        state && (
                            <div className="row g-4">
                                <div className="col-12">
                                    <h6>{tr.invite || 'Einladen'}</h6>
                                    <div className="position-relative" id="selectAtendeeArea" tabIndex={-1}>
                                        <input
                                            type="text"
                                            autoComplete="off"
                                            className="form-control"
                                            placeholder={tr.searchPlaceholder || 'Suchen'}
                                            value={term}
                                            disabled={adding}
                                            onChange={(e) => handleSearch(e.target.value)}
                                        />
                                        {searching && (
                                            <div className="position-absolute top-100 start-0 w-100 text-center py-1 small text-muted">
                                                <i className="fas fa-spinner fa-spin" />
                                            </div>
                                        )}
                                        {!searching && showSearchResults && (
                                            <div className="position-relative">
                                                <SearchResults
                                                    results={results!}
                                                    busy={adding}
                                                    translations={tr}
                                                    onPickUser={handlePickUser}
                                                    onPickGroup={handlePickGroup}
                                                />
                                            </div>
                                        )}
                                    </div>
                                    <hr />
                                    <h6>
                                        {tr.invited || 'Eingeladen'}
                                        {state.canPrintParticipants && state.printUrl && (
                                            <a
                                                href={state.printUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="ms-1"
                                            >
                                                <i className="fa fa-print" />
                                            </a>
                                        )}
                                    </h6>
                                    <div className="attendeeScroll" ref={listRef}>
                                        <ul id="atendeeList" className="mb-0">
                                            {allParticipants.map((participant) =>
                                                participant ? (
                                                    <ParticipantRow
                                                        key={participant.id}
                                                        participant={participant}
                                                        organizer={participant.isOrganizer}
                                                        translations={tr}
                                                        onAction={handleAction}
                                                    />
                                                ) : null
                                            )}
                                        </ul>

                                        {state.waitinglist.length > 0 && (
                                            <>
                                                <b>{tr.waitinglist || 'Warteliste'}</b>
                                                <hr />
                                                <ul className="ps-3">
                                                    {state.waitinglist.map((entry) => (
                                                        <li key={entry.id}>
                                                            {entry.email}
                                                            <a
                                                                className="small ms-2"
                                                                href="#"
                                                                onClick={(e) => {
                                                                    e.preventDefault();
                                                                    acceptWaitinglist(entry).catch(() => {});
                                                                }}
                                                            >
                                                                <i className="fa fa-check" />
                                                            </a>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {state.allowBulkInvite && (
                                    <div className="col-12">
                                        <button
                                            type="button"
                                            className="btn btn-primary"
                                            aria-expanded={showBulkInvite}
                                            onClick={() => setShowBulkInvite((prev) => !prev)}
                                        >
                                            {tr.bulkInvite || 'Mehrere E-Mail-Adressen auf einmal eingeben'}
                                        </button>
                                        {showBulkInvite && (
                                            <div className="mt-3">
                                                <form onSubmit={handleBulkInvite}>
                                                    <div className="form-group">
                                                        <label htmlFor="bulkMember">
                                                            {tr.bulkInviteLabel || ''}
                                                        </label>
                                                        <textarea
                                                            id="bulkMember"
                                                            className="form-control"
                                                            rows={3}
                                                            value={bulkMember}
                                                            disabled={busy}
                                                            onChange={(e) => setBulkMember(e.target.value)}
                                                        />
                                                        <small className="form-text text-muted">
                                                            {tr.bulkInviteHelp || ''}
                                                        </small>
                                                    </div>
                                                    <button
                                                        type="submit"
                                                        className="btn btn-outline-primary mt-2"
                                                        disabled={busy || !bulkMember.trim()}
                                                    >
                                                        {busy ? (
                                                            <>
                                                                <i className="fas fa-spinner fa-spin me-1" />
                                                                {tr.bulkInviteSubmit || ''}
                                                            </>
                                                        ) : (
                                                            tr.bulkInviteSubmit || ''
                                                        )}
                                                    </button>
                                                </form>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )
                    )}
                </div>
                <div className="modal-footer">
                    <a className="btn btn-outline-danger" href="#" onClick={(e) => { e.preventDefault(); hide(); }}>
                        {tr.close || 'Schließen'}
                    </a>
                </div>
            </div>
        </div>,
        host
    );
}
