import { describe, expect, it } from 'vitest';
import { statusTone, transitionLabel } from './status';

describe('status helpers', () => {
    it('prioritizes overdue tone', () => {
        expect(statusTone('in_progress', true)).toBe('late');
    });

    it('красит принятые, рабочие и неподтверждённые разными синими', () => {
        expect(statusTone('new', false)).toBe('accepted');
        expect(statusTone('assigned', false)).toBe('accepted');
        expect(statusTone('in_progress', false)).toBe('progress');
        expect(statusTone('returned', false)).toBe('progress');
        expect(statusTone('done', false)).toBe('ready');
    });

    it('зелёный только после подтверждения, серый после передачи', () => {
        expect(statusTone('confirmed', false)).toBe('done');
        expect(statusTone('redirected', true)).toBe('muted');
    });

    it('uses a return-specific action label', () => {
        expect(transitionLabel('in_progress', 'returned')).toBe('Снова в работу');
    });

    it('labels completion through the close flow', () => {
        expect(transitionLabel('done', 'in_progress')).toBe('Отметить выполненной');
    });
});
