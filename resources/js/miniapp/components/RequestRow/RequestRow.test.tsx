import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { RequestRow } from './RequestRow';

const item: Parameters<typeof RequestRow>[0]['item'] = {
    id: 12,
    status: 'in_progress',
    category: 'Протечка',
    address: 'ул. Мира, 12',
    entrance: 1,
    flat: '5',
    participants_count: 0,
    is_overdue: false,
    deadline_fix_at: new Date(Date.now() + 26 * 3_600_000).toISOString(),
};

describe('строка заявки', () => {
    it('показывает, сколько осталось, пока заявку делают', () => {
        render(<RequestRow item={item} onOpen={vi.fn()} />);

        expect(screen.getByText(/^осталось/)).toBeInTheDocument();
    });

    it('не считает срок у выполненной заявки и пишет, что ждём жителя', () => {
        render(<RequestRow item={{ ...item, status: 'done' }} onOpen={vi.fn()} />);

        expect(screen.getByText('Выполнено')).toBeInTheDocument();
        expect(screen.getByText('Ждёт подтверждения жителя')).toBeInTheDocument();
        expect(screen.queryByText(/осталось|срок вышел/)).not.toBeInTheDocument();
    });

    it('ничего не пишет про срок у подтверждённой заявки', () => {
        render(<RequestRow item={{ ...item, status: 'confirmed' }} onOpen={vi.fn()} />);

        expect(screen.queryByText(/осталось|срок вышел|Ждёт подтверждения/)).not.toBeInTheDocument();
    });
});
