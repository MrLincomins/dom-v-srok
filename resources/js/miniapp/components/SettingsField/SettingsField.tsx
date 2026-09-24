import { useState, useRef, type ComponentProps, type ReactNode } from 'react';
import { FieldClear } from '@/components/FieldClear';

export function SettingsField({
    label,
    placeholder,
    error,
    invalid,
    after,
    clearable = true,
    onChange,
    onInput,
    disabled,
    value,
    defaultValue,
    type,
    ...props
}: {
    label: string;
    error?: string;
    invalid?: boolean;
    after?: ReactNode;
    clearable?: boolean;
} & Omit<ComponentProps<'input'>, 'size'>) {
    const marked = Boolean(error || invalid);
    const inputRef = useRef<HTMLInputElement>(null);
    const controlled = value !== undefined;
    const [localFilled, setLocalFilled] = useState(() => String(defaultValue ?? '').length > 0);
    const filled = controlled ? String(value ?? '').length > 0 : localFilled;
    const canClear =
        clearable && type !== 'date' && type !== 'password' && !disabled && filled && (controlled ? Boolean(onChange) : true);

    const handleInput: ComponentProps<'input'>['onInput'] = (event) => {
        if (!controlled) setLocalFilled(event.currentTarget.value.length > 0);
        onInput?.(event);
    };

    const clear = () => {
        const input = inputRef.current;
        if (!input) return;
        const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set;
        setter?.call(input, '');
        if (onChange) {
            onChange({
                target: input,
                currentTarget: input,
            } as Parameters<NonNullable<typeof onChange>>[0]);
        }
        if (!controlled) setLocalFilled(false);
        input.focus();
    };

    return (
        <div className="min-w-0">
            <label
                className={`settings-field${marked ? ' is-invalid' : ''}${type === 'date' ? ' is-date' : ''}${after ? ' has-extra' : ''}`}
            >
                <input
                    ref={inputRef}
                    className="settings-field-value"
                    placeholder={placeholder || ' '}
                    aria-invalid={marked ? true : undefined}
                    type={type}
                    disabled={disabled}
                    value={value}
                    defaultValue={defaultValue}
                    onChange={onChange}
                    onInput={handleInput}
                    {...props}
                />
                <span className="settings-field-label">{label}</span>
                {canClear ? <FieldClear onClear={clear} /> : null}
                {after}
            </label>
            {error ? (
                <p className="settings-field-error" role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
