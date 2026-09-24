import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import { QueuePage } from '@/features/queue/QueuePage';

const auth: AuthState = {
    status: 'ready',
    user: { id: 1, name: 'Диспетчер', role: 'dispatcher', is_demo: true },
    error: null,
    loginDemo: vi.fn(),
    logout: vi.fn(),
    refresh: vi.fn(),
};

describe('очередь заявок', () => {
    it('показывает пустое состояние вкладки', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                json({
                    data: [],
                    meta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 30,
                        total: 0,
                        counters: { new: 0, in_progress: 0, overdue: 0, closed: 0 },
                    },
                }),
            ),
        );
        renderQueue();
        expect(await screen.findByText('Новых заявок нет.')).toBeInTheDocument();
    });

    it('показывает ошибку и повтор', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => new Response('nope', { status: 500 })));
        renderQueue();
        expect(await screen.findByRole('button', { name: 'Повторить' })).toBeInTheDocument();
    });

    it('открывает заявку из списка', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                json({
                    data: [
                        {
                            id: 17,
                            status: 'new',
                            status_label: 'Принято',
                            category: 'Вода',
                            description: 'Течёт',
                            address: 'ул. Мира, 12',
                            responsible_name: 'ТСЖ',
                            is_sure: true,
                            is_overdue: false,
                            participants_count: 1,
                            created_at: '2026-09-20T10:00:00Z',
                        },
                    ],
                    meta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 30,
                        total: 1,
                        counters: { new: 1, in_progress: 0, overdue: 0, closed: 0 },
                    },
                }),
            ),
        );
        renderQueue();
        expect(await screen.findByText(/№ 17/)).toBeInTheDocument();
    });

    it('показывает крестик в поиске, когда есть текст', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                json({
                    data: [],
                    meta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 30,
                        total: 0,
                        counters: { new: 0, in_progress: 0, overdue: 0, closed: 0 },
                    },
                }),
            ),
        );
        renderQueue();
        const search = await screen.findByLabelText('Найти: номер, квартира или улица');
        expect(screen.queryByRole('button', { name: 'Очистить' })).not.toBeInTheDocument();
        fireEvent.focus(search);
        fireEvent.change(search, { target: { value: 'мира' } });
        fireEvent.click(screen.getByRole('button', { name: 'Очистить' }));
        expect(search).toHaveValue('');
        expect(screen.queryByRole('button', { name: 'Очистить' })).not.toBeInTheDocument();
    });

    it('в поиске считает заявки по каждой вкладке', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = new URL(String(input), 'http://localhost');
                const q = url.searchParams.get('q') ?? '';
                if (q === '') {
                    return queuePage(3, { new: 3, in_progress: 3, overdue: 1, closed: 4 });
                }
                const status = url.searchParams.get('status');
                const overdue = url.searchParams.get('overdue');
                if (status === 'new') return queuePage(2);
                if (status === 'active') return queuePage(1);
                if (status === 'open' && overdue === '1') return queuePage(0);
                if (status === 'closed') return queuePage(0);
                return queuePage(0);
            }),
        );
        renderQueue();
        expect(await screen.findByRole('tab', { name: /Новые/ })).toHaveTextContent('3');
        fireEvent.change(screen.getByLabelText('Найти: номер, квартира или улица'), {
            target: { value: 'проф' },
        });
        await waitFor(() => {
            expect(screen.getByRole('tab', { name: /Новые/ })).toHaveTextContent('2');
        });
        expect(screen.getByRole('tab', { name: /В работе/ })).toHaveTextContent('1');
        expect(screen.getByRole('tab', { name: /Просрочено/ })).not.toHaveTextContent(/\d/);
        expect(screen.getByRole('tab', { name: /Закрытые/ })).not.toHaveTextContent(/\d/);
    });
});

function queuePage(total: number, counters = { new: 0, in_progress: 0, overdue: 0, closed: 0 }) {
    return json({
        data: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 30,
            total,
            counters,
        },
    });
}

function json(body: unknown) {
    return new Response(JSON.stringify(body), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
    });
}

function renderQueue() {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    return render(
        <MaxUI platform="android" colorScheme="light">
            <MemoryRouter>
                <QueryClientProvider client={client}>
                    <AuthContext.Provider value={auth}>
                        <QueuePage />
                    </AuthContext.Provider>
                </QueryClientProvider>
            </MemoryRouter>
        </MaxUI>,
    );
}
