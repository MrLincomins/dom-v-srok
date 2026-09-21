import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import type { User } from '@/api/types';
import { CreateRequestPage } from './CreateRequestPage';

const user: User = {
    id: 2,
    name: 'Житель',
    role: 'resident',
    is_demo: true,
    entrance: 2,
    flat: '45',
    organization: {
        id: 1,
        name: 'Мир',
        type: 'tsj',
        phone_ads: '112',
        direct_contracts: { cold_water: false, hot_water: false, heat: false, power: false, tko: false },
        is_demo: true,
    },
    house: {
        id: 1,
        address: 'ул. Мира, 12',
        entrances: 2,
        qr_token: 'qr',
        chat_bound: true,
        chat_keywords_enabled: false,
        chat_pinned: false,
        qr_url: 'https://max.ru/qr.png',
        start_url: 'https://max.ru/start',
    },
};

beforeEach(() => {
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
            const url = String(input);
            const method = init?.method ?? 'GET';
            if (url.includes('/catalog/categories')) {
                return json({
                    data: [
                        {
                            id: 10,
                            slug: 'light',
                            name: 'Свет',
                            is_emergency: false,
                            verify: false,
                            children: [
                                {
                                    id: 11,
                                    slug: 'entrance.light',
                                    name: 'Не горит свет в подъезде',
                                    is_emergency: false,
                                    verify: false,
                                    basis: 'Госстрой 170',
                                },
                            ],
                        },
                    ],
                });
            }
            if (url.endsWith('/requests') && method === 'POST') {
                return json(
                    {
                        data: {
                            id: 44,
                            status: 'new',
                            status_label: 'Принято',
                            allowed_transitions: [],
                            category: { id: 11, name: 'Не горит свет в подъезде', slug: 'entrance.light' },
                            description: 'Темно',
                            house: user.house,
                            responsible: { kind: 'organization', name: 'ТСЖ', is_sure: true },
                            basis: 'Госстрой 170',
                            is_overdue: false,
                            closed_late: false,
                            returned_count: 0,
                            participants_count: 0,
                            origin: 'api',
                            created_at: '2026-09-21T10:00:00Z',
                        },
                    },
                    201,
                );
            }
            return json({ data: {} }, 404);
        }),
    );
});

describe('новая заявка жителя', () => {
    it('проходит шаги и отправляет заявку', async () => {
        renderPage();
        fireEvent.click(await screen.findByRole('button', { name: 'Нет, не авария' }));
        fireEvent.click(await screen.findByText('Свет'));
        fireEvent.click(await screen.findByText('Не горит свет в подъезде'));
        fireEvent.change(screen.getByPlaceholderText('Коротко, что не так'), { target: { value: 'Темно' } });
        fireEvent.click(screen.getByRole('button', { name: 'Дальше' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Как в прошлый раз' }));
        fireEvent.click(await screen.findByRole('button', { name: 'Отправить' }));
        await waitFor(() => {
            const call = vi
                .mocked(fetch)
                .mock.calls.find(
                    ([url, init]) => String(url).endsWith('/requests') && String(init?.method) === 'POST',
                );
            expect(call?.[1]?.body).toContain('"category_id":11');
            expect(call?.[1]?.body).toContain('Темно');
            expect(call?.[1]?.body).toContain('"entrance":2');
        });
    });
});

function json(body: unknown, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}

function renderPage() {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    const auth: AuthState = {
        status: 'ready',
        user,
        error: null,
        loginDemo: vi.fn(),
        logout: vi.fn(),
        refresh: vi.fn(),
    };

    return render(
        <MaxUI platform="android" colorScheme="light">
            <MemoryRouter>
                <QueryClientProvider client={client}>
                    <AuthContext.Provider value={auth}>
                        <CreateRequestPage />
                    </AuthContext.Provider>
                </QueryClientProvider>
            </MemoryRouter>
        </MaxUI>,
    );
}
