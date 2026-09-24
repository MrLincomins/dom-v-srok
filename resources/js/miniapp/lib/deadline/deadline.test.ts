import { describe, expect, it } from 'vitest';
import { describeDeadline, plural } from './deadline';

const now = new Date('2026-09-17T12:00:00Z');

describe('describeDeadline', () => {
    it('shows remaining hours', () => {
        expect(describeDeadline('2026-09-17T17:00:00Z', now)).toEqual({
            text: 'осталось 5 ч',
            overdue: false,
            urgent: false,
        });
    });

    it('marks urgent under four hours', () => {
        expect(describeDeadline('2026-09-17T14:00:00Z', now).urgent).toBe(true);
    });

    it('shows overdue days', () => {
        expect(describeDeadline('2026-09-15T12:00:00Z', now)).toEqual({
            text: 'срок вышел на 2 дня',
            overdue: true,
            urgent: false,
        });
    });

    it('handles missing deadline', () => {
        expect(describeDeadline(null, now).text).toBe('срок по договору');
    });
});

describe('plural', () => {
    it('picks russian forms', () => {
        expect(plural(1, 'день', 'дня', 'дней')).toBe('день');
        expect(plural(3, 'день', 'дня', 'дней')).toBe('дня');
        expect(plural(11, 'день', 'дня', 'дней')).toBe('дней');
        expect(plural(25, 'день', 'дня', 'дней')).toBe('дней');
    });
});
