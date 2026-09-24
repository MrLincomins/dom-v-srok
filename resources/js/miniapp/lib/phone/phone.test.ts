import { describe, expect, it } from 'vitest';
import { isCompletePhone, localPhoneDigits, telHref, toStoredPhone } from './phone';

describe('телефон для звонка', () => {
    it('убирает пробелы и скобки', () => {
        expect(telHref('+7 843 000-00-01')).toBe('tel:+78430000001');
        expect(telHref('112')).toBe('tel:112');
    });
});

describe('телефон в поле', () => {
    it('оставляет 10 цифр без +7 и 8', () => {
        expect(localPhoneDigits('+79172472389')).toBe('9172472389');
        expect(localPhoneDigits('89172472389')).toBe('9172472389');
        expect(localPhoneDigits('917 247-23-89')).toBe('9172472389');
    });

    it('собирает номер для API только из 10 цифр', () => {
        expect(isCompletePhone('9172472389')).toBe(true);
        expect(isCompletePhone('917')).toBe(false);
        expect(toStoredPhone('9172472389')).toBe('+79172472389');
        expect(toStoredPhone('')).toBeNull();
        expect(toStoredPhone('917')).toBeNull();
    });
});
