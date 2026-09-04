import React, { useEffect, useRef } from 'react';

function disposeEntryMdb(container) {
    if (!container || !window.mdb) {
        return;
    }
    container.querySelectorAll('[data-mdb-dropdown-init]').forEach((el) => {
        window.mdb.Dropdown.getInstance(el)?.dispose();
    });
    container.querySelectorAll('[data-mdb-popover-init]').forEach((el) => {
        window.mdb.Popover.getInstance(el)?.dispose();
    });
    container.querySelectorAll('[data-mdb-tooltip-init]').forEach((el) => {
        window.mdb.Tooltip.getInstance(el)?.dispose();
    });
}

export default function AddressBookEntry({ contact, status, tr, onToggleFavorite, onToggleDeputy, onDelete }) {
    const entryRef = useRef(null);
    const starClass = contact.isFavorite ? 'fa isAddressbookFavorite fa-star' : 'far fa-star';
    const statusAttr = status || undefined;

    // When React removes or relocates an entry (favourite toggle moves contacts between
    // sections, filters hide them), the MDB instances attached to the old nodes must be
    // disposed. Orphaned instances hold references to detached DOM nodes and make
    // subsequent MDB interactions (e.g. opening a dropdown) hang.
    useEffect(() => {
        return () => {
            disposeEntryMdb(entryRef.current);
        };
    }, []);

    return (
        <li
            ref={entryRef}
            className="list-group-item adressbookline"
            data-uid={contact.uid}
            data-indexer={contact.indexer}
            data-status={statusAttr}
            data-filterafter={JSON.stringify(contact.categories)}
        >
            <span className="breakWord d-flex align-items-center name">
                {contact.profilePicture ? (
                    <div className="profilepicture me-2" style={{ backgroundImage: `url('${contact.profilePicture}')` }}>
                        <div className="dot with-icon" />
                    </div>
                ) : (
                    <div className="profilepicture me-2" style={{ backgroundColor: `#${contact.color}` }}>
                        {contact.avatarText}
                        <div className="dot with-icon" />
                    </div>
                )}
                <div className="d-flex flex-column">
                    <p className="mb-0">
                        {contact.name}
                        <small>
                            <a href={contact.favoriteUrl} onClick={(e) => { e.preventDefault(); onToggleFavorite(); }}>
                                <i className={starClass} />
                            </a>
                        </small>
                        {contact.isDeputy && (
                            <i className="ms-2 fa-solid fa-file-contract" data-mdb-tooltip-init title={tr.deputyTooltip} />
                        )}
                    </p>
                    <p className="mb-0"><small>{contact.username}</small></p>
                </div>
            </span>
            <span className="noBreak d-flex align-items-center icon">
                {contact.adhoc.length === 1 ? (
                    <a className="text-success adhocConfirm me-2" data-text={tr.adhocText} href={contact.adhoc[0].url}>
                        <i className="fa-solid fa-phone-volume" />
                    </a>
                ) : (
                    <>
                        <a className="caretdown dropdown-toggle text-success" data-mdb-dropdown-init aria-haspopup="true" aria-expanded="false">
                            <i className="fa-solid fa-phone-volume" />
                        </a>
                        <div className="dropdown-menu">
                            {contact.adhoc.map((s) => (
                                <a key={s.url} className="dropdown-item adhocConfirm" data-text={tr.adhocText} href={s.url}>
                                    {s.serverName}
                                </a>
                            ))}
                        </div>
                    </>
                )}
                <a className="dropdown-toggle ms-3" type="button" data-mdb-dropdown-init aria-haspopup="true" aria-expanded="false" data-mdb-auto-close="outside">
                    <i className="fas fa-ellipsis-v" />
                </a>
                <ul className="dropdown-menu">
                    {contact.canMakeDeputy && (
                        <li className="dropdown-item d-flex">
                            {contact.isDeputyFromLdap ? (
                                <>
                                    <a className="disabled">
                                        <i className="fa-solid fa-file-contract" />
                                        {tr.deputyLdapDisabled}
                                    </a>
                                    <a className="ms-2" tabIndex="0" data-mdb-popover-init data-mdb-trigger="focus" data-mdb-content={tr.deputyHelpLdap}>
                                        <i className="fa fa-question-circle" />
                                    </a>
                                </>
                            ) : (
                                <>
                                    <a className={contact.isDeputy ? 'text-success isDeputy' : ''} href={contact.deputyUrl} onClick={(e) => { e.preventDefault(); onToggleDeputy(); }}>
                                        <i className="fa-solid fa-file-contract" />
                                        {contact.isDeputy ? tr.deputyRemove : tr.deputyAdd}
                                    </a>
                                    <a className="ms-2" tabIndex="0" data-mdb-popover-init data-mdb-trigger="focus" data-mdb-content={tr.deputyHelp}>
                                        <i className="fa fa-question-circle" />
                                    </a>
                                </>
                            )}
                        </li>
                    )}
                    {contact.canDelete && (
                        <>
                            <div className="dropdown-divider" />
                            <li>
                                <a className="dropdown-item" href={contact.removeUrl} onClick={(e) => { e.preventDefault(); onDelete(); }}>
                                    <i className="fa fa-trash text-danger" />
                                    {tr.delete}
                                </a>
                            </li>
                        </>
                    )}
                </ul>
            </span>
        </li>
    );
}
