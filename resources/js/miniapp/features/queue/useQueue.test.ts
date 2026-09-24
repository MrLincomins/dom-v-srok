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

    it('меняет счётчик текущей вкладки на total поиска', () => {
        expect(countersForSearch(counters, 'in_progress', 'te', 1)).toEqual({
            ...counters,
            in_progress: 1,
        });
    });

    it('не трогает счётчики без поисковой строки', () => {
        expect(countersForSearch(counters, 'in_progress', '  ', 1)).toEqual(counters);
    });
});
