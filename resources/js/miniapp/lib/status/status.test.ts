import { describe, expect, it } from 'vitest';
import { staffPrimaryAction, statusExplain, statusTone } from './status';

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

    it('пишет сводку по роли', () => {
        expect(statusExplain('done', false, 'staff')).toBe('Ждём, что скажет житель');
        expect(statusExplain('done', false, 'resident')).toBe('Сделали. Подтвердите, что всё хорошо');
    });

    it('выбирает главную кнопку диспетчера', () => {
        expect(staffPrimaryAction('new')).toBe('assign');
        expect(staffPrimaryAction('returned')).toBe('assign');
        expect(staffPrimaryAction('assigned')).toBe('start');
        expect(staffPrimaryAction('in_progress')).toBe('finish');
        expect(staffPrimaryAction('done')).toBeNull();
    });
});
