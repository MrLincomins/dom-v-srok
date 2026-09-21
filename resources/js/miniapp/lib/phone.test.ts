import { describe, expect, it } from 'vitest';
import { isCompletePhone, localPhoneDigits, toStoredPhone } from './phone';

describe('телефон', () => {
    it('оставляет 10 цифр и отбрасывает +7 / 8', () => {
        expect(localPhoneDigits('9172472389')).toBe('9172472389');
        expect(localPhoneDigits('+79172472389')).toBe('9172472389');
        expect(localPhoneDigits('89172472389')).toBe('9172472389');
        expect(localPhoneDigits('7 917 247-23-89')).toBe('9172472389');
    });

    it('собирает номер для API только из полного локального', () => {
        expect(toStoredPhone('9172472389')).toBe('+79172472389');
        expect(toStoredPhone('')).toBeNull();
        expect(isCompletePhone('9172472389')).toBe(true);
        expect(isCompletePhone('917')).toBe(false);
    });
});
