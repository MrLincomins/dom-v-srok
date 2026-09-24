import { describe, expect, it } from 'vitest';
import { countersForSearch, tabToQuery } from '@/features/queue/useQueue';

describe('tabToQuery', () => {
    it('maps overdue to open overdue requests', () => {
        expect(tabToQuery('overdue', '  Мира  ')).toEqual({
            status: 'open',
            overdue: true,
            q: 'Мира',
        });
    });

    it('does not send an empty search', () => {
        expect(tabToQuery('closed', '   ')).toEqual({ status: 'closed', q: undefined });
    });

    it('maps the in-progress tab to the active group', () => {
        expect(tabToQuery('in_progress', '')).toEqual({ status: 'active', q: undefined });
    });
});

describe('countersForSearch', () => {
    const counters = { new: 1, in_progress: 3, overdue: 1, closed: 4 };

    it('подставляет total поиска во все вкладки', () => {
        expect(
            countersForSearch(counters, 'te', { new: 0, in_progress: 1, overdue: 0, closed: 0 }),
        ).toEqual({
            new: 0,
            in_progress: 1,
            overdue: 0,
            closed: 0,
        });
    });

    it('не трогает счётчики без поисковой строки', () => {
        expect(countersForSearch(counters, '  ', { in_progress: 1 })).toEqual(counters);
    });

    it('без total поиска не оставляет общие счётчики', () => {
        expect(countersForSearch(counters, 'te')).toEqual({
            new: 0,
            in_progress: 0,
            overdue: 0,
            closed: 0,
        });
    });
});
