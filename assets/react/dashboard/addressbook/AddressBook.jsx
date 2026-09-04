import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Swal from 'sweetalert2';
import AddressBookEntry from './AddressBookEntry';
import AddContactModal from './AddContactModal';
import { addContact, postContactAction } from './addressBookApi';
import { getCookie, setCookie } from '../../../js/cookie';

function normalizeFilterValue(value) {
    return Array.isArray(value) ? value : [value];
}

function findCommonElements(filterArr, content) {
    for (let i = 0; i < filterArr.length; i++) {
        const filter = normalizeFilterValue(filterArr[i]);
        let found = false;
        for (let j = 0; j < filter.length; j++) {
            if (content.indexOf(filter[j]) !== -1) {
                found = true;
                break;
            }
        }
        if (!found) {
            return false;
        }
    }
    return true;
}

function initMdbInContainer(container) {
    if (!container || !window.mdb) {
        return;
    }
    container.querySelectorAll('[data-mdb-dropdown-init]').forEach((el) => {
        window.mdb.Dropdown.getOrCreateInstance(el);
    });
    container.querySelectorAll('[data-mdb-popover-init]').forEach((el) => {
        window.mdb.Popover.getOrCreateInstance(el);
    });
    container.querySelectorAll('[data-mdb-tooltip-init]').forEach((el) => {
        window.mdb.Tooltip.getOrCreateInstance(el);
    });
}

export default function AddressBook({ initialState }) {
    const config = (initialState && initialState.config) || {};
    const initialContacts = (initialState && initialState.contacts) || [];
    const filters = (initialState && initialState.filters) || [];
    const translations = config.translations || {};

    const [contacts, setContacts] = useState(initialContacts);
    const [search, setSearch] = useState('');
    const [checkedFilters, setCheckedFilters] = useState(() => {
        const init = {};
        filters.forEach((f) => {
            init[f.id] = getCookie(f.id) === 'true';
        });
        return init;
    });
    const [statusByUid, setStatusByUid] = useState({});
    const [activeLetter, setActiveLetter] = useState(null);
    const [showAddModal, setShowAddModal] = useState(false);
    const [pendingFavoriteUid, setPendingFavoriteUid] = useState(null);

    const rootRef = useRef(null);
    const contentRef = useRef(null);
    const contactsRef = useRef(contacts);
    contactsRef.current = contacts;

    useEffect(() => {
        const handler = (e) => {
            const data = e.detail || {};
            setStatusByUid((prev) => {
                const next = { ...prev };
                const knownUids = new Set();
                Object.keys(data).forEach((status) => {
                    (data[status] || []).forEach((uid) => {
                        next[uid] = status;
                        knownUids.add(uid);
                    });
                });
                contactsRef.current.forEach((c) => {
                    if (!knownUids.has(c.uid)) {
                        next[c.uid] = 'offline';
                    }
                });
                return next;
            });
        };
        window.addEventListener('addressbook:onlineStatus', handler);
        return () => window.removeEventListener('addressbook:onlineStatus', handler);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
        const container = rootRef.current;
        if (!container) {
            return;
        }
        initMdbInContainer(container);
    }, [contacts, search, checkedFilters, statusByUid]);

    const toggleFilter = useCallback((id) => {
        setCheckedFilters((prev) => {
            const next = { ...prev, [id]: !prev[id] };
            setCookie(id, next[id] ? 'true' : 'false', 365);
            return next;
        });
    }, []);

    const checkedCount = filters.filter((f) => checkedFilters[f.id]).length;

    const effectiveCategories = useCallback(
        (contact) => {
            const cats = [...contact.categories];
            const status = statusByUid[contact.uid];
            if (status && cats.indexOf(status) === -1) {
                cats.push(status);
            }
            return cats;
        },
        [statusByUid]
    );

    const visibleContacts = useMemo(() => {
        const value = search.toLowerCase();
        const filterArr = filters.filter((f) => checkedFilters[f.id]).map((f) => f.value);

        return contacts.filter((contact) => {
            if (value) {
                if (!contact.indexer) {
                    return true;
                }
                if (contact.indexer.toLowerCase().indexOf(value) === -1) {
                    return false;
                }
            }
            const content = effectiveCategories(contact);
            if (content.length === 0) {
                return true;
            }
            const arr = filterArr.length === 0 ? [['all']] : filterArr;
            return findCommonElements(arr, content);
        });
    }, [contacts, search, checkedFilters, filters, effectiveCategories]);

    const favorites = useMemo(
        () =>
            visibleContacts
                .filter((c) => c.isFavorite)
                .sort((a, b) => (a.email || '').toLowerCase().localeCompare((b.email || '').toLowerCase())),
        [visibleContacts]
    );

    const hasAnyFavorites = useMemo(() => contacts.some((c) => c.isFavorite), [contacts]);

    const generalGroups = useMemo(() => {
        const sorted = [...visibleContacts].sort((a, b) =>
            (a.nameNoIcon || '').toLowerCase().localeCompare((b.nameNoIcon || '').toLowerCase())
        );
        const groups = [];
        sorted.forEach((contact) => {
            const letter = contact.initial;
            const last = groups[groups.length - 1];
            if (!last || last.initial !== letter) {
                groups.push({ initial: letter, contacts: [contact] });
            } else {
                last.contacts.push(contact);
            }
        });
        return groups;
    }, [visibleContacts]);

    const toggleFavorite = useCallback(
        async (contact) => {
            if (pendingFavoriteUid !== null) {
                return;
            }
            setPendingFavoriteUid(contact.uid);
            try {
                const data = await postContactAction(contact.favoriteUrl);
                if (data && data.ok === true) {
                    setContacts((prev) =>
                        prev.map((c) => {
                            if (c.uid !== contact.uid) {
                                return c;
                            }
                            const isFavorite = !c.isFavorite;
                            const categories = c.categories.filter((cat) => cat !== 'favorite');
                            if (isFavorite) {
                                categories.push('favorite');
                            }
                            return { ...c, isFavorite, categories };
                        })
                    );
                }
            } catch (e) {
                // Non-optimistic: keep the UI state unchanged on failure.
            } finally {
                setPendingFavoriteUid(null);
            }
        },
        [pendingFavoriteUid]
    );

    const toggleDeputy = useCallback(async (contact) => {
        try {
            const data = await postContactAction(contact.deputyUrl);
            if (data && data.ok === true) {
                setContacts((prev) =>
                    prev.map((c) => (c.uid === contact.uid ? { ...c, isDeputy: !c.isDeputy } : c))
                );
            }
        } catch (e) {
            // keep UI unchanged
        }
    }, []);

    const deleteContact = useCallback(
        (contact) => {
            Swal.fire({
                title: translations.confirmTitle || 'Bestätigung',
                text: translations.confirmDelete || '',
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: translations.confirmCancel || 'Abbrechen',
                confirmButtonText: translations.confirmOk || 'OK',
                heightAuto: false,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton: 'btn-outline-primary btn',
                },
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }
                postContactAction(contact.removeUrl)
                    .then((data) => {
                        if (data && data.ok === true) {
                            setContacts((prev) => prev.filter((c) => c.id !== contact.id));
                        } else if (data && data.error) {
                            Swal.fire({
                                title: translations.errorTitle || 'Fehler',
                                text: data.error,
                                icon: 'error',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#3085d6',
                            });
                        }
                    })
                    .catch(() => {});
            });
        },
        [translations]
    );

    const handleAddContact = useCallback(
        async (email) => {
            const data = await addContact(config.urls.addAjax, email);
            if (data && data.ok === true && data.contact) {
                setContacts((prev) => {
                    if (prev.some((c) => c.uid === data.contact.uid)) {
                        return prev;
                    }
                    return [...prev, data.contact];
                });
                return { ok: true };
            }
            return { ok: false, error: (data && data.error) || translations.errorTitle };
        },
        [config, translations]
    );

    const scrollToLetter = useCallback(
        (letter) => {
            const target = document.getElementById(`adressbook_${letter}`);
            const content = contentRef.current;
            const modal = document.getElementById('modalAdressbook');
            if (!target || !content || !modal) {
                return;
            }
            const headerH = modal.querySelector('.modal-header')?.clientHeight || 0;
            const topbarH = modal.querySelector('.topbar')?.clientHeight || 0;
            const tabsH = modal.querySelector('.nav-mat-tabs')?.clientHeight || 0;
            const position = target.getBoundingClientRect().top + window.scrollY;
            const actPosition = content.scrollTop;
            const diff = position + actPosition - headerH - topbarH - tabsH - 13;
            content.scrollTo({ top: diff, behavior: 'smooth' });
            setActiveLetter(letter);
        },
        []
    );

    const entryProps = (contact) => ({
        contact,
        status: statusByUid[contact.uid],
        tr: translations,
        onToggleFavorite: () => toggleFavorite(contact),
        onToggleDeputy: () => toggleDeputy(contact),
        onDelete: () => deleteContact(contact),
    });

    return (
        <div ref={rootRef} className="d-flex h-100 adressbookComponent" id="adressbookModalTabContent">
            <div className="register flex-shrink-0">
                <div className="mb-2" style={{ height: 39 }} />
                <div className="flex-grow-1 d-flex flex-column mb-3 capital-Letter">
                    {generalGroups.map((group) => (
                        <div
                            key={group.initial}
                            className={`registerElement${activeLetter === group.initial ? ' adressBookPointOut' : ''}`}
                        >
                            <a
                                className="adressbookSearchletter"
                                href="#"
                                data-target={`#adressbook_${group.initial}`}
                                onClick={(e) => {
                                    e.preventDefault();
                                    scrollToLetter(group.initial);
                                }}
                            >
                                {group.initial}
                            </a>
                        </div>
                    ))}
                </div>
            </div>

            <div className="textarea">
                {config.doAllowUserCreation && (
                    <button
                        type="button"
                        className="btn-outline-primary btn w-100 mb-2"
                        onClick={() => setShowAddModal(true)}
                    >
                        {translations.newContact || 'Neuer Kontakt'}
                    </button>
                )}
                <div className="topbar">
                    <div className="form-grou mt-2 mb-2">
                        <input
                            type="text"
                            placeholder={translations.search || 'Suche'}
                            className="form-control searchListInput"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                    <div className="filterbar d-flex justify-content-end mt-4">
                        <div className="dropdown me-2">
                            <a href="#" type="button" id="dropdownMenuButton" data-mdb-dropdown-init aria-expanded="false">
                                {translations.filter || 'Filter'}
                                <i className="fas fa-filter">
                                    <div className={`filter-dot${checkedCount > 0 ? '' : ' d-none'}`}>{checkedCount}</div>
                                </i>
                            </a>
                            <ul className="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                {filters.map((f) => (
                                    <li key={f.id} className="adressBookFilterLine">
                                        <a
                                            className="dropdown-item"
                                            href="#"
                                            onClick={(e) => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                if (e.target.matches('input') || e.target.matches('label')) {
                                                    return;
                                                }
                                                toggleFilter(f.id);
                                            }}
                                        >
                                            <div className="form-check">
                                                <input
                                                    className="form-check-input adressBookFilter"
                                                    type="checkbox"
                                                    data-filter={JSON.stringify(f.value)}
                                                    id={f.id}
                                                    checked={!!checkedFilters[f.id]}
                                                    onChange={() => toggleFilter(f.id)}
                                                />
                                                <label className="form-check-label" htmlFor={f.id}>
                                                    {f.label}
                                                </label>
                                            </div>
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="content" ref={contentRef}>
                    <ul className="list-group">
                        <li className="list-group-item capital-Letter">
                            <b>
                                <i className="fa fa-star" /> {translations.favoritesTitle || 'Favoriten'}
                            </b>
                        </li>
                        {!hasAnyFavorites ? (
                            <li
                                className="list-group-item adressbookline"
                                dangerouslySetInnerHTML={{ __html: translations.favoritesHelp || '' }}
                            />
                        ) : (
                            favorites.map((contact) => <AddressBookEntry key={contact.uid} {...entryProps(contact)} />)
                        )}
                        <li className="mb-3" />
                        {generalGroups.map((group) => (
                            <React.Fragment key={group.initial}>
                                <li
                                    className={`list-group-item capital-Letter${activeLetter === group.initial ? ' adressBookPointOut' : ''}`}
                                    id={`adressbook_${group.initial}`}
                                >
                                    {group.initial}
                                </li>
                                {group.contacts.map((contact) => (
                                    <AddressBookEntry key={contact.uid} {...entryProps(contact)} />
                                ))}
                            </React.Fragment>
                        ))}
                    </ul>
                </div>
            </div>

            {showAddModal && (
                <AddContactModal
                    translations={translations}
                    onSubmit={handleAddContact}
                    onClose={() => setShowAddModal(false)}
                />
            )}
        </div>
    );
}
