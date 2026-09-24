import { describe, expect, it } from 'vitest';
import { daysBetween, formatDateInput, startOfMonthInput } from './dates';

describe('даты журнала', () => {
    it('даёт YYYY-MM-DD и первое число месяца', () => {
        const date = new Date('2026-09-21T12:00:00+03:00');
        expect(formatDateInput(date)).toBe('2026-09-21');
        expect(startOfMonthInput(date)).toBe('2026-09-01');
    });

    it('считает дни периода включительно по разнице дат', () => {
        expect(daysBetween('2026-09-01', '2026-09-21')).toBe(20);
        expect(daysBetween('2026-09-10', '2026-09-01')).toBe(-9);
        expect(daysBetween('вчера', '2026-09-01')).toBeNull();
    });
});
