import type { ChangeEvent } from 'react';

export function CompactNote({
    label,
    value,
    onChange,
    placeholder,
}: {
    label?: string;
    value: string;
    onChange: (event: ChangeEvent<HTMLTextAreaElement>) => void;
    placeholder: string;
}) {
    return (
        <label className="compact-note">
            {label ? <span className="compact-note-label">{label}</span> : null}
            <textarea
                className="compact-note-input"
                placeholder={placeholder}
                value={value}
                onChange={onChange}
                rows={2}
            />
        </label>
    );
}
