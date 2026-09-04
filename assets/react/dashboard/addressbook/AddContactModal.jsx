import React, { useState } from 'react';
import Swal from 'sweetalert2';

export default function AddContactModal({ translations, onSubmit, onClose }) {
    const [email, setEmail] = useState('');
    const [saving, setSaving] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (saving || !email) {
            return;
        }
        setSaving(true);
        try {
            const res = await onSubmit(email);
            if (res && res.ok) {
                onClose();
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

    return (
        <>
            <div className="modal-backdrop fade show" />
            <div
                className="modal fade show"
                style={{ display: 'block' }}
                tabIndex="-1"
                role="dialog"
                aria-labelledby="addressbookAddContactLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
                    <div className="modal-content">
                        <div className="modal-header light-blue darken-3 white-text">
                            <h5 className="modal-title">{translations.newContact || 'Neuer Kontakt'}</h5>
                            <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
                        </div>
                        <div className="modal-body">
                            <form onSubmit={handleSubmit}>
                                <div className="row d-flex align-items-center textarea">
                                    <div className="col-lg-8">
                                        <div className="form-group">
                                            <input
                                                type="email"
                                                name="email"
                                                id="newContactEmail"
                                                className="form-control"
                                                placeholder={translations.email || 'E-Mail-Adresse'}
                                                value={email}
                                                onChange={(e) => setEmail(e.target.value)}
                                                required
                                                autoFocus
                                            />
                                        </div>
                                    </div>
                                    <div className="col-lg-4">
                                        <button type="submit" className="btn btn-primary btn-sm" disabled={saving}>
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
                </div>
            </div>
        </>
    );
}
