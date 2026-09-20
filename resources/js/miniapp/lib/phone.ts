/** Российский мобильный: в поле только 10 цифр, без +7. */

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

export function toStoredPhone(raw: string): string | null {
    const local = localPhoneDigits(raw);
    if (local.length === 0) return null;
    return `+7${local}`;
}
