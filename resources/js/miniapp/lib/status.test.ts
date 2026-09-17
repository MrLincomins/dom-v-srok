import { describe, expect, it } from 'vitest';
import { statusTone, transitionLabel } from './status';

describe('status helpers', () => {
    it('prioritizes overdue tone', () => {
        expect(statusTone('in_progress', true)).toBe('late');
    });

    it('uses a return-specific action label', () => {
        expect(transitionLabel('in_progress', 'returned')).toBe('Снова в работу');
    });

    it('labels completion through the close flow', () => {
        expect(transitionLabel('done', 'in_progress')).toBe('Отметить выполненной');
    });
});
