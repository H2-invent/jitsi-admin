import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import Swal from 'sweetalert2';
import { cleanupOrphanedModalBackdrops } from '../utils/mdb';
import type { Translations } from '../types';

export interface AddContactResult {
    ok: boolean;
    error?: string;
}

export interface AddContactModalProps {
    translations: Translations;
    onSubmit: (email: string) => Promise<AddContactResult>;
    onClose: () => void;
}

export default function AddContactModal({ translations, onSubmit, onClose }: AddContactModalProps) {
    const [email, setEmail] = useState('');
    const [saving, setSaving] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
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
                if (inputRef.current) {
                    inputRef.current.focus();
                }
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
            if (inputRef.current) {
                inputRef.current.focus();
            }
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

    const hide = () => {
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
    };

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
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
