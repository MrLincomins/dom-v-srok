import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { CompactNote } from '@/components/CompactNote';
import { PhoneField } from '@/components/PhoneField';
import { SettingsField } from '@/components/SettingsField';

describe('очистка полей', () => {
    it('показывает крестик в SettingsField, когда поле в фокусе и есть текст', () => {
        const onChange = vi.fn();
        render(<SettingsField label="Имя" value="Мир" onChange={onChange} />);
        fireEvent.focus(screen.getByLabelText('Имя'));
        fireEvent.click(screen.getByRole('button', { name: 'Очистить' }));
        expect(onChange).toHaveBeenCalledOnce();
        const event = onChange.mock.calls[0]?.[0] as { target: { value: string } };
        expect(event.target.value).toBe('');
    });

    it('не показывает крестик в пустом SettingsField', () => {
        render(<SettingsField label="Имя" value="" onChange={() => undefined} />);
        expect(screen.queryByRole('button', { name: 'Очистить' })).not.toBeInTheDocument();
    });

    it('показывает крестик в неконтролируемом поле с текстом', () => {
        render(<SettingsField label="Подъезды" defaultValue="2" />);
        fireEvent.focus(screen.getByLabelText('Подъезды'));
        expect(screen.getByRole('button', { name: 'Очистить' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Очистить' }));
        expect(screen.getByLabelText('Подъезды')).toHaveValue('');
        expect(screen.queryByRole('button', { name: 'Очистить' })).not.toBeInTheDocument();
    });

    it('не показывает крестик у поля даты', () => {
        render(<SettingsField label="С" type="date" value="2026-09-01" onChange={() => undefined} />);
        fireEvent.focus(screen.getByLabelText('С'));
        expect(screen.queryByRole('button', { name: 'Очистить' })).not.toBeInTheDocument();
    });

    it('показывает крестик в PhoneField, когда есть цифры', () => {
        const onChange = vi.fn();
        render(<PhoneField label="Телефон" value="9000000013" onChange={onChange} />);
        fireEvent.focus(screen.getByLabelText('Телефон'));
        fireEvent.click(screen.getByRole('button', { name: 'Очистить' }));
        expect(onChange).toHaveBeenCalledWith('');
    });

    it('оставляет подпись службы у заполненного телефона', () => {
        render(<PhoneField label="Аварийная служба" value="8430000001" onChange={() => undefined} />);
        expect(screen.getByLabelText('Аварийная служба')).toHaveValue('(843) 000-00-01');
        expect(screen.getByText('Аварийная служба')).toBeVisible();
    });

    it('показывает крестик в CompactNote, когда есть текст', () => {
        const onChange = vi.fn();
        render(<CompactNote value="Течёт" placeholder="Комментарий" onChange={onChange} />);
        fireEvent.focus(screen.getByPlaceholderText('Комментарий'));
        fireEvent.click(screen.getByRole('button', { name: 'Очистить' }));
        expect(onChange).toHaveBeenCalledOnce();
        const event = onChange.mock.calls[0]?.[0] as { target: { value: string } };
        expect(event.target.value).toBe('');
    });
});
