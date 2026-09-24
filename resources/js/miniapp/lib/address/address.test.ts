import { describe, expect, it } from 'vitest';
import { withoutCity, withoutMarks } from './address';

describe('адрес без города', () => {
    it('снимает Казань перед улицей', () => {
        expect(withoutCity('Казань, ул. Демонстрационная, д. 1')).toBe('ул. Демонстрационная, д. 1');
        expect(withoutCity('г. Казань, ул. Мира, 12')).toBe('ул. Мира, 12');
    });

    it('не трогает адрес без города', () => {
        expect(withoutCity('ул. Мира, 12')).toBe('ул. Мира, 12');
    });
});

describe('адрес без сокращений', () => {
    it('убирает ул. и д.', () => {
        expect(withoutMarks('Казань, ул. Демонстрационная, д. 1')).toBe('Демонстрационная, 1');
        expect(withoutMarks('ул. Мира, 12')).toBe('Мира, 12');
    });
});
