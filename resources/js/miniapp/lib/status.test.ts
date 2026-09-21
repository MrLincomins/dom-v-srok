import { describe, expect, it } from 'vitest';
import { statusTone, transitionLabel } from './status';

describe('status helpers', () => {
    it('prioritizes overdue tone', () => {
        expect(statusTone('in_progress', true)).toBe('late');
    });

    it('красит принятые и рабочие статусы синим', () => {
        expect(statusTone('new', false)).toBe('accepted');
        expect(statusTone('assigned', false)).toBe('work');
        expect(statusTone('in_progress', false)).toBe('work');
        expect(statusTone('done', false)).toBe('work');
    });

    it('uses a return-specific action label', () => {
        expect(transitionLabel('in_progress', 'returned')).toBe('Снова в работу');
    });

    it('labels completion through the close flow', () => {
        expect(transitionLabel('done', 'in_progress')).toBe('Отметить выполненной');
    });
});
