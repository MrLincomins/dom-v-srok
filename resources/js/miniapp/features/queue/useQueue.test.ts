import { describe, expect, it } from 'vitest';
import { tabToQuery } from './useQueue';

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
