/** В поле лежат 10 цифр, без +7. */

export function localPhoneDigits(raw: string): string {
    const digits = raw.replace(/\D/g, '');
    if (digits.length >= 11 && (digits.startsWith('7') || digits.startsWith('8'))) {
        return digits.slice(1, 11);
    }
    return digits.slice(0, 10);
}

export function isCompletePhone(raw: string): boolean {
    return localPhoneDigits(raw).length === 10;
}

/** Как показываем номер: (917) 247-23-89 */
export function formatLocalPhone(raw: string): string {
    const digits = localPhoneDigits(raw);
    if (digits.length === 0) return '';
    if (digits.length <= 3) return `(${digits}`;
    if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
    if (digits.length <= 8) return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 8)}-${digits.slice(8)}`;
}

export function toStoredPhone(raw: string): string | null {
    const local = localPhoneDigits(raw);
    if (local.length === 0) return null;
    if (local.length !== 10) return null;
    return `+7${local}`;
}

/** Для звонка убираем пробелы и скобки. */
export function telHref(phone: string): string {
    return `tel:${phone.replace(/[^\d+]/g, '')}`;
}
