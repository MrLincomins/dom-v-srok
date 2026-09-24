import { describe, expect, it } from 'vitest';
import { formatLocalPhone, isCompletePhone, localPhoneDigits, telHref, toStoredPhone } from './phone';

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

    it('рисует номер как (000) 000-00-00', () => {
        expect(formatLocalPhone('')).toBe('');
        expect(formatLocalPhone('917')).toBe('(917');
        expect(formatLocalPhone('917247')).toBe('(917) 247');
        expect(formatLocalPhone('91724723')).toBe('(917) 247-23');
        expect(formatLocalPhone('9172472389')).toBe('(917) 247-23-89');
        expect(formatLocalPhone('+7 (917) 247-23-89')).toBe('(917) 247-23-89');
    });
});
