import React from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import type { DashboardActionItem } from '../types';

interface ActionLinkProps {
    item: DashboardActionItem;
    className?: string;
}

export function ActionLink({ item, className }: ActionLinkProps) {
    const classes = [className, ...(item.classes || [])].filter(Boolean).join(' ');
    const dataProps: Record<string, string> = {};
    if (item.data) {
        const data = item.data;
        Object.keys(data).forEach((key) => {
            dataProps[`data-${key}`] = data[key];
        });
    }
    const target = item.target || undefined;
    return (
        <a
            href={item.href}
            className={classes}
            target={target as React.HTMLAttributeAnchorTarget | undefined}
            rel={target === '_blank' ? 'noopener' : undefined}
            data-text={item.confirmText || undefined}
            {...dataProps}
        >
            {item.icon ? <i className={item.icon} /> : null}
            {item.label ? ` ${item.label}` : ''}
        </a>
    );
}

interface OptionDropdownProps {
    items: DashboardActionItem[];
    translationKey: string;
}

export default function OptionDropdown({ items, translationKey }: OptionDropdownProps) {
    const { config } = React.useContext(DashboardConfigContext)!;
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
