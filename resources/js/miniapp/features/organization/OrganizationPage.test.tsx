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
    chat_pinned: false,
    chat_keywords_enabled: false,
    start_url: 'https://max.ru/start',
    qr_url: 'https://max.example/qr.png?signature=x',
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
            if (url.endsWith('/organization/executors') && method === 'POST') {
                return json({ data: { id: 6, name: 'Пётр', specialty: null, phone: '+79172472389' } });
            }
            if (url.endsWith('/organization/executors')) return json({ data: [{ id: 5, name: 'Иван', specialty: null, phone: null }] });
            if (url.includes('/organization/executors/5') && method === 'DELETE') {
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
        expect(
            vi.mocked(fetch).mock.calls.some(
                ([url, init]) =>
                    String(url).includes('/organization/executors/5') && String(init?.method) === 'DELETE',
            ),
        ).toBe(false);
        expect(screen.getByRole('dialog', { name: 'Убрать этого исполнителя?' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Да, убрать' }));
        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(
                    ([url, init]) =>
                        String(url).includes('/organization/executors/5') && String(init?.method) === 'DELETE',
                ),
            ).toBe(true);
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Исполнитель убран');
    });

    it('сохраняет контакты и показывает уведомление', async () => {
        renderPage();
        fireEvent.click(await screen.findByRole('button', { name: 'Сохранить' }));
        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(
                    ([url, init]) => String(url).endsWith('/organization') && String(init?.method) === 'PATCH',
                ),
            ).toBe(true);
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Сохранено');
    });

    it('принимает телефон исполнителя без +7', async () => {
        renderPage();
        await screen.findByDisplayValue('Мир');
        const name = screen.getByLabelText('Имя');
        const phone = screen.getByLabelText('Телефон');
        fireEvent.change(name, { target: { value: 'Пётр' } });
        fireEvent.change(phone, { target: { value: '9172472389' } });
        fireEvent.click(screen.getByRole('button', { name: 'Добавить исполнителя' }));
        await waitFor(() => {
            const call = vi.mocked(fetch).mock.calls.find(
                ([url, init]) => String(url).endsWith('/organization/executors') && String(init?.method) === 'POST',
            );
            expect(call?.[1]?.body).toContain('+79172472389');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Исполнитель добавлен');
    });

    it('копирует ссылку для жителя из списка домов', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.assign(navigator, { clipboard: { writeText } });
        renderPage();
        fireEvent.click(await screen.findByText('Ссылка для жителя'));
        await waitFor(() => {
            expect(writeText).toHaveBeenCalledWith('https://max.ru/start');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Ссылка скопирована');
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
