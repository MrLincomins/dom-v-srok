import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it, vi } from 'vitest';
import type { RequestCard } from '@/api/types';
import { StaffRequestActions } from './StaffRequestActions';

const card = {
    id: 17,
    status: 'new',
    status_label: 'Принято',
    allowed_transitions: ['in_progress', 'redirected'],
    category: { id: 1, name: 'Вода', slug: 'water' },
    description: 'Течёт',
    house: {
        id: 1,
        address: 'ул. Мира, 12',
        entrances: 1,
        qr_token: 'qr',
        chat_bound: true,
        chat_pinned: false,
        chat_keywords_enabled: true,
        start_url: 'https://max.ru',
        qr_url: 'https://max.example/qr.png?signature=x',
    },
    responsible: { kind: 'organization', name: 'ТСЖ', phone: null, is_sure: true, party_id: null },
    basis: 'ПП',
    is_overdue: false,
    closed_late: false,
    executor: null,
    returned_count: 0,
    participants_count: 2,
    origin: 'qr',
    created_at: '2026-09-20T10:00:00Z',
} as RequestCard;

describe('действия диспетчера', () => {
    it('назначает исполнителя по нажатию на ячейку', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);
                if (url.endsWith('/organization/executors')) {
                    return json({
                        data: [{ id: 5, name: 'Иван', specialty: 'сантехник', phone: null }],
                    });
                }
                if (url.endsWith('/requests/17/assign')) {
                    return json({ data: { ...card, executor: { id: 5, name: 'Иван' } } });
                }
                return json({ data: card });
            }),
        );

        const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
        render(
            <MaxUI platform="android" colorScheme="light">
                <QueryClientProvider client={client}>
                    <StaffRequestActions card={card} />
                </QueryClientProvider>
            </MaxUI>,
        );

        expect(await screen.findByText('Иван')).toBeInTheDocument();
        fireEvent.click(screen.getByText('Иван'));

        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(
                    ([url, init]) =>
                        String(url).includes('/requests/17/assign') &&
                        String(init?.body).includes('"executor_id":5'),
                ),
            ).toBe(true);
        });
    });
});

function json(body: unknown, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}
