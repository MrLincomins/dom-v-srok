import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import { QueuePage } from './QueuePage';

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
});

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
