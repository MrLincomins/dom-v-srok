import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { OrganizationPage } from './OrganizationPage';

const organization = {
    id: 1,
    name: 'Мир',
    type: 'tsj',
    phone_ads: '112',
    phone_dispatch: null,
    email: null,
    reception_hours: null,
    reception_address: null,
    direct_contracts: {
        cold_water: false,
        hot_water: false,
        heat: true,
        power: false,
        tko: false,
    },
    is_demo: true,
};

const house = {
    id: 8,
    address: 'ул. Мира, 12',
    entrances: 2,
    qr_token: 'qr',
    chat_bound: true,
    chat_keywords_enabled: false,
    start_url: 'https://max.ru/start',
};

beforeEach(() => {
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
            const url = String(input);
            const method = init?.method ?? 'GET';
            if (url.endsWith('/organization/houses/8') && method === 'PATCH') {
                return json({ data: { ...house, chat_keywords_enabled: true } });
            }
            if (url.endsWith('/organization/houses')) return json({ data: [house] });
            if (url.endsWith('/organization/executors')) return json({ data: [{ id: 5, name: 'Иван', specialty: null, phone: null }] });
            if (url.includes('/organization/executors/5') && method === 'DELETE') {
                return json({ data: { ok: true } });
            }
            if (url.endsWith('/demo/reset') && method === 'POST') {
                return json({ data: { ok: true } });
            }
            if (url.endsWith('/organization') && method === 'PATCH') {
                return json({
                    data: {
                        ...organization,
                        direct_contracts: { ...organization.direct_contracts, heat: false },
                    },
                });
            }
            if (url.endsWith('/organization')) return json({ data: organization });
            return json({ data: {} }, 404);
        }),
    );
});

describe('организация', () => {
    it('показывает карточку и включает присоединение в чате', async () => {
        renderPage();

        expect(await screen.findByDisplayValue('Мир')).toBeInTheDocument();
        const join = await screen.findByLabelText('Предлагать присоединиться');
        fireEvent.click(join);

        await waitFor(() => {
            const calls = vi.mocked(fetch).mock.calls;
            expect(
                calls.some(
                    ([url, init]) =>
                        String(url).includes('/organization/houses/8') &&
                        String(init?.method) === 'PATCH' &&
                        String(init?.body).includes('chat_keywords_enabled'),
                ),
            ).toBe(true);
        });
        fireEvent.click(screen.getByRole('button', { name: 'Убрать' }));
        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(
                    ([url, init]) =>
                        String(url).includes('/organization/executors/5') && String(init?.method) === 'DELETE',
                ),
            ).toBe(true);
        });
    });

    it('сбрасывает демо-данные', async () => {
        renderPage();
        fireEvent.click(await screen.findByRole('button', { name: 'Сбросить демо-данные' }));
        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(
                    ([url, init]) => String(url).includes('/demo/reset') && String(init?.method) === 'POST',
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

function renderPage() {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    return render(
        <MaxUI platform="android" colorScheme="light">
            <MemoryRouter>
                <QueryClientProvider client={client}>
                    <OrganizationPage />
                </QueryClientProvider>
            </MemoryRouter>
        </MaxUI>,
    );
}
