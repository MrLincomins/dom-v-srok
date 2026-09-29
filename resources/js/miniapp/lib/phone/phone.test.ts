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
        expect(localPhoneDigits('+79000000013')).toBe('9000000013');
        expect(localPhoneDigits('89000000013')).toBe('9000000013');
        expect(localPhoneDigits('900 000-00-13')).toBe('9000000013');
    });

    it('собирает номер для API только из 10 цифр', () => {
        expect(isCompletePhone('9000000013')).toBe(true);
        expect(isCompletePhone('900')).toBe(false);
        expect(toStoredPhone('9000000013')).toBe('+79000000013');
        expect(toStoredPhone('')).toBeNull();
        expect(toStoredPhone('900')).toBeNull();
    });

    it('рисует номер как (000) 000-00-00', () => {
        expect(formatLocalPhone('')).toBe('');
        expect(formatLocalPhone('900')).toBe('(900');
        expect(formatLocalPhone('900000')).toBe('(900) 000');
        expect(formatLocalPhone('90000000')).toBe('(900) 000-00');
        expect(formatLocalPhone('9000000013')).toBe('(900) 000-00-13');
        expect(formatLocalPhone('+7 (900) 000-00-13')).toBe('(900) 000-00-13');
    });
});
