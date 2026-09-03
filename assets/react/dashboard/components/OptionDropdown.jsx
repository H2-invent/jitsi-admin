import React from 'react';
import { DashboardConfigContext } from '../DashboardPage';

export function ActionLink({ item, className }) {
    const classes = [className, ...(item.classes || [])].filter(Boolean).join(' ');
    const dataProps = {};
    if (item.data) {
        Object.keys(item.data).forEach((key) => {
            dataProps[`data-${key}`] = item.data[key];
        });
    }
    return (
        <a
            href={item.href}
            className={classes}
            target={item.target}
            rel={item.target === '_blank' ? 'noopener' : undefined}
            data-text={item.confirmText}
            {...dataProps}
        >
            {item.icon ? <i className={item.icon} /> : null}
            {item.label ? ` ${item.label}` : ''}
        </a>
    );
}

export default function OptionDropdown({ items, translationKey }) {
    const { config } = React.useContext(DashboardConfigContext);
    const tr = config.translations;
    if (!items || items.length === 0) {
        return null;
    }
    return (
        <div className="dropdown element moderator-options">
            <button
                className="btn caretdown btn-outline-primary dropdown-toggle"
                type="button"
                id="dropdownMenu1"
                data-mdb-dropdown-init
                aria-haspopup="true"
                aria-expanded="false"
            >
                {tr[translationKey]}
            </button>
            <ul className="dropdown-menu p-1" aria-labelledby="dropdownMenu1">
                {items.map((item) => (
                    <li key={item.key}>
                        <ActionLink item={item} className="dropdown-item" />
                    </li>
                ))}
            </ul>
        </div>
    );
}
