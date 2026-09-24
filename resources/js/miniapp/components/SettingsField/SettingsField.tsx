import type { ComponentProps } from 'react';

export function SettingsField({
    label,
    placeholder,
    error,
    ...props
}: { label: string; error?: string } & Omit<ComponentProps<'input'>, 'size'>) {
    return (
        <div className="min-w-0">
            <label className={`settings-field${error ? ' is-invalid' : ''}${props.type === 'date' ? ' is-date' : ''}`}>
                <input
                    className="settings-field-value"
                    placeholder={placeholder || ' '}
                    aria-invalid={error ? true : undefined}
                    {...props}
                />
                <span className="settings-field-label">{label}</span>
            </label>
            {error ? (
                <p className="settings-field-error" role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
