import { texts } from '@/app/texts';
import { localPhoneDigits } from '@/lib/phone';

export function PhoneField({
    label,
    value,
    onChange,
    error,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="min-w-0">
            <label className={`settings-field settings-phone${error ? ' is-invalid' : ''}`}>
                <span className="settings-phone-prefix" aria-hidden>
                    {texts.organization.phonePrefix}
                </span>
                <input
                    className="settings-field-value"
                    placeholder={label}
                    inputMode="numeric"
                    autoComplete="tel-national"
                    maxLength={10}
                    value={value}
                    aria-label={label}
                    onChange={(event) => onChange(localPhoneDigits(event.target.value))}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? 'phone-field-error' : undefined}
                />
            </label>
            {error ? (
                <p id="phone-field-error" className="settings-field-error" role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
