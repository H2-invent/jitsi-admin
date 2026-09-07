import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import Swal from 'sweetalert2';

export default function AddContactModal({ translations, onSubmit, onClose }) {
    const [email, setEmail] = useState('');
    const [saving, setSaving] = useState(false);
    const inputRef = useRef(null);
    const onCloseRef = useRef(onClose);
    // A fresh MDB modal host appended to <body>, so the dialog is rendered and
    // positioned exactly like every other app modal (e.g. the "New Group" modal)
    // instead of being nested inside the (transformed) address book sidebar modal,
    // which confined it to the sidebar width and broke focus/click handling.
    const [host] = useState(() => {
        const el = document.createElement('div');
        el.className = 'modal fade';
        el.setAttribute('tabindex', '-1');
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-hidden', 'true');
        document.body.appendChild(el);
        return el;
    });

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

    useEffect(() => {
        const ModalCtor = window.mdb && window.mdb.Modal;
        const instance = ModalCtor ? ModalCtor.getOrCreateInstance(host) : null;
        const frame = requestAnimationFrame(() => {
            if (instance) {
                instance.show();
            } else {
                host.classList.add('show');
                host.style.display = 'block';
            }
            if (inputRef.current) {
                inputRef.current.focus();
            }
        });

        const handleHidden = () => onCloseRef.current();
        host.addEventListener('hidden.bs.modal', handleHidden);

        return () => {
            cancelAnimationFrame(frame);
            host.removeEventListener('hidden.bs.modal', handleHidden);
            if (ModalCtor && ModalCtor.getInstance(host)) {
                ModalCtor.getInstance(host).dispose();
            }
            host.remove();
        };
    }, [host]);

    const hide = () => {
        const ModalCtor = window.mdb && window.mdb.Modal;
        const instance = ModalCtor ? ModalCtor.getOrCreateInstance(host) : null;
        if (instance) {
            instance.hide();
        } else {
            host.classList.remove('show');
            host.style.display = 'none';
            onCloseRef.current();
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (saving || !email) {
            return;
        }
        setSaving(true);
        try {
            const res = await onSubmit(email);
            if (res && res.ok) {
                hide();
            } else {
                Swal.fire({
                    title: translations.errorTitle || 'Fehler',
                    text: (res && res.error) || '',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6',
                });
            }
        } catch (err) {
            Swal.fire({
                title: translations.errorTitle || 'Fehler',
                text: translations.errorDefault || 'Fehler',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
            });
        } finally {
            setSaving(false);
        }
    };

    return createPortal(
        <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
            <div className="modal-content">
                <div className="modal-header light-blue darken-3 white-text">
                    <h5 className="modal-title">{translations.newContact || 'Neuer Kontakt'}</h5>
                    <button type="button" className="btn-close" aria-label="Close" onClick={hide} />
                </div>
                <div className="modal-body">
                    <form onSubmit={handleSubmit}>
                        <div className="row textarea">
                            <div className="col-lg-8">
                                <div className="form-group mb-0">
                                    <input
                                        ref={inputRef}
                                        type="email"
                                        name="email"
                                        className="form-control"
                                        placeholder={translations.email || 'E-Mail-Adresse'}
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        required
                                    />
                                </div>
                            </div>
                            <div className="col-lg-4 d-flex align-items-start">
                                <button type="submit" className="btn btn-primary btn-sm mt-0" disabled={saving}>
                                    {saving ? (
                                        <>
                                            <i className="fas fa-spinner fa-spin" />{' '}
                                            {translations.save || 'Speichern'}
                                        </>
                                    ) : (
                                        translations.save || 'Speichern'
                                    )}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>,
        host
    );
}
