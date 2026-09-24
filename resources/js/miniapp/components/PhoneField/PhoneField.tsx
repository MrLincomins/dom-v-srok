import { FieldClear } from '@/components/FieldClear';
import { texts } from '@/app/texts';
import { formatLocalPhone, localPhoneDigits } from '@/lib/phone';

export function PhoneField({
    label,
    value,
    onChange,
    error,
    invalid,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    invalid?: boolean;
}) {
    const marked = Boolean(error || invalid);
    return (
        <div className="min-w-0">
            <label className={`settings-field settings-phone${marked ? ' is-invalid' : ''}`}>
                <span className="settings-phone-prefix" aria-hidden>
                    {texts.organization.phonePrefix}
                </span>
                <input
                    className="settings-field-value"
                    placeholder={label}
                    inputMode="numeric"
                    autoComplete="tel-national"
                    maxLength={15}
                    value={formatLocalPhone(value)}
                    aria-label={label}
                    onChange={(event) => onChange(localPhoneDigits(event.target.value))}
                    aria-invalid={marked ? true : undefined}
                    aria-describedby={error ? 'phone-field-error' : undefined}
                />
                {value.length > 0 ? <FieldClear onClear={() => onChange('')} /> : null}
            </label>
            {error ? (
                <p id="phone-field-error" className="settings-field-error" role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
