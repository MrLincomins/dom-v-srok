import { useRef, type ChangeEvent } from 'react';
import { FieldClear } from '@/components/FieldClear';

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
    const inputRef = useRef<HTMLTextAreaElement>(null);

    const clear = () => {
        const input = inputRef.current;
        if (!input) return;
        const setter = Object.getOwnPropertyDescriptor(HTMLTextAreaElement.prototype, 'value')?.set;
        setter?.call(input, '');
        onChange({ target: input, currentTarget: input } as ChangeEvent<HTMLTextAreaElement>);
        input.focus();
    };

    return (
        <label className="compact-note">
            {label ? <span className="compact-note-label">{label}</span> : null}
            <span className={`compact-note-field${value.length > 0 ? ' is-filled' : ''}`}>
                <textarea
                    ref={inputRef}
                    className="compact-note-input"
                    placeholder={placeholder}
                    value={value}
                    onChange={onChange}
                    rows={2}
                />
                {value.length > 0 ? <FieldClear onClear={clear} /> : null}
            </span>
        </label>
    );
}
