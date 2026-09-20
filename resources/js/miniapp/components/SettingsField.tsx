import type { ComponentProps } from 'react';

export function SettingsField({
    label,
    ...props
}: { label: string } & Omit<ComponentProps<'input'>, 'size'>) {
    return (
        <label className="settings-field">
            <span className="settings-field-label">{label}</span>
            <input className="settings-field-value" {...props} />
        </label>
    );
}
