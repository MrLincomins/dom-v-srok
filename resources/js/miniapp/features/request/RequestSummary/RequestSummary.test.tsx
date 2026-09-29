import { render, screen } from '@testing-library/react';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it } from 'vitest';
import type { RequestCard } from '@/api/types';
import { RequestSummary } from './RequestSummary';

const card = {
    id: 21,
    status: 'in_progress',
    category: { id: 1, name: 'Вода', slug: 'water' },
    description: 'Течёт кран',
    house: { id: 1, address: 'ул. Мира, 12' },
    entrance: 1,
    flat: '5',
    is_overdue: false,
    deadline_fix_at: new Date(Date.now() + 26 * 3_600_000).toISOString(),
    returned_count: 0,
    participants_count: 0,
} as RequestCard;

describe('сводка заявки', () => {
    it('показывает, сколько осталось, пока заявку делают', () => {
        renderSummary(card);

        expect(screen.getByText(/^осталось/)).toBeInTheDocument();
    });

    it('не считает срок у выполненной заявки', () => {
        renderSummary({ ...card, status: 'done' });

        expect(screen.getByText('Ждём, что скажет житель')).toBeInTheDocument();
        expect(screen.queryByText(/осталось|срок вышел/)).not.toBeInTheDocument();
    });

    it('называет, кому передали заявку', () => {
        renderSummary({
            ...card,
            status: 'redirected',
            redirected_to: { name: 'Водоканал', phone: '+78432000000', party: null, note: null },
        });

        expect(screen.getByText('Кому передали')).toBeInTheDocument();
        expect(screen.getByText('Водоканал · +78432000000')).toBeInTheDocument();
        expect(screen.queryByText(/осталось|срок вышел/)).not.toBeInTheDocument();
    });
});

function renderSummary(value: RequestCard) {
    return render(
        <MaxUI platform="android" colorScheme="light">
            <RequestSummary card={value} audience="staff" />
        </MaxUI>,
    );
}
