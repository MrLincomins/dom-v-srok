import { describe, expect, it } from 'vitest';
import { daysAgoInput, daysBetween, formatDateInput } from './dates';

describe('даты журнала', () => {
    it('даёт YYYY-MM-DD и дату на 30 дней раньше по Москве', () => {
        const date = new Date('2026-09-21T12:00:00+03:00');
        expect(formatDateInput(date)).toBe('2026-09-21');
        expect(daysAgoInput(30, date)).toBe('2026-08-22');
        expect(daysAgoInput(30, new Date('2026-10-01T00:30:00+03:00'))).toBe('2026-09-01');
    });

    it('считает дни периода включительно по разнице дат', () => {
        expect(daysBetween('2026-09-01', '2026-09-21')).toBe(20);
        expect(daysBetween('2026-09-10', '2026-09-01')).toBe(-9);
        expect(daysBetween('вчера', '2026-09-01')).toBeNull();
    });
});
