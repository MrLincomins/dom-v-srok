import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { OrganizationPage } from '@/features/organization/OrganizationPage';

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
            if (url.endsWith('/organization/executors'))
                return json({ data: [{ id: 5, name: 'Иван', specialty: null, phone: null }] });
            if (url.includes('/organization/executors/5') && method === 'DELETE') {
                return json({ data: { ok: true } });
            }
            if (url.endsWith('/organization/contractors') && method === 'POST') {
                return json(
                    {
                        data: {
                            id: 3,
                            type: 'lift',
                            type_label: 'Лифтовая организация',
                            name: 'Лифт-Сервис',
                            phone: '+79172472389',
                        },
                    },
                    201,
                );
            }
            if (url.includes('/organization/contractors/4') && method === 'DELETE') {
                return json({ data: { ok: true } });
            }
            if (url.endsWith('/organization/contractors')) return json({ data: [] });
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
            vi
                .mocked(fetch)
                .mock.calls.some(
                    ([url, init]) =>
                        String(url).includes('/organization/executors/5') &&
                        String(init?.method) === 'DELETE',
                ),
        ).toBe(false);
        expect(screen.getByRole('dialog', { name: 'Убрать этого исполнителя?' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Да, убрать' }));
        await waitFor(() => {
            expect(
                vi
                    .mocked(fetch)
                    .mock.calls.some(
                        ([url, init]) =>
                            String(url).includes('/organization/executors/5') &&
                            String(init?.method) === 'DELETE',
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
                vi
                    .mocked(fetch)
                    .mock.calls.some(
                        ([url, init]) =>
                            String(url).endsWith('/organization') && String(init?.method) === 'PATCH',
                    ),
            ).toBe(true);
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Сохранено');
    });

    it('принимает телефон исполнителя без +7', async () => {
        renderPage();
        await screen.findByDisplayValue('Мир');
        const form = screen.getByRole('button', { name: 'Добавить исполнителя' }).closest('form');
        expect(form).toBeTruthy();
        const name = within(form as HTMLElement).getByLabelText('Имя');
        const phone = within(form as HTMLElement).getByLabelText('Телефон');
        fireEvent.change(name, { target: { value: 'Пётр' } });
        fireEvent.change(phone, { target: { value: '9172472389' } });
        fireEvent.click(screen.getByRole('button', { name: 'Добавить исполнителя' }));
        await waitFor(() => {
            const call = vi
                .mocked(fetch)
                .mock.calls.find(
                    ([url, init]) =>
                        String(url).endsWith('/organization/executors') && String(init?.method) === 'POST',
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

    it('добавляет подрядчика и показывает QR дома', async () => {
        renderPage();
        await screen.findByDisplayValue('Мир');
        expect(screen.getByText('Журнал заявок')).toBeInTheDocument();
        const qr = await screen.findByRole('img', { name: 'QR-код дома' });
        expect(qr).toHaveAttribute('src', 'https://max.example/qr.png?signature=x');
        fireEvent.click(screen.getByRole('button', { name: '2' }));
        expect(screen.getByRole('img', { name: 'QR-код дома' })).toHaveAttribute(
            'src',
            'https://max.example/qr.png?signature=x&entrance=2',
        );
        expect(screen.getByText('Скачать QR')).toBeInTheDocument();

        const form = screen.getByRole('button', { name: 'Добавить подрядчика' }).closest('form');
        expect(form).toBeTruthy();
        fireEvent.click(within(form as HTMLElement).getByRole('button', { name: 'Лифт' }));
        fireEvent.change(within(form as HTMLElement).getByLabelText('Название'), {
            target: { value: 'Лифт-Сервис' },
        });
        fireEvent.change(within(form as HTMLElement).getByLabelText('Телефон'), {
            target: { value: '9172472389' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Добавить подрядчика' }));
        await waitFor(() => {
            const call = vi
                .mocked(fetch)
                .mock.calls.find(
                    ([url, init]) =>
                        String(url).endsWith('/organization/contractors') && String(init?.method) === 'POST',
                );
            expect(call?.[1]?.body).toContain('"type":"lift"');
            expect(call?.[1]?.body).toContain('Лифт-Сервис');
            expect(call?.[1]?.body).toContain('+79172472389');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Подрядчик добавлен');
    });

    it('показывает, как привязать чат, если он ещё не привязан', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);
                if (url.endsWith('/organization/houses')) {
                    return json({ data: [{ ...house, chat_bound: false }] });
                }
                if (url.endsWith('/organization/executors')) return json({ data: [] });
                if (url.endsWith('/organization/contractors')) return json({ data: [] });
                if (url.endsWith('/organization')) return json({ data: organization });
                return json({ data: {} }, 404);
            }),
        );
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.assign(navigator, { clipboard: { writeText } });
        renderPage();
        fireEvent.click(await screen.findByText('Как привязать чат'));
        await waitFor(() => {
            expect(writeText).toHaveBeenCalledWith('/дом qr');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Команда скопирована');
    });

    it('просит подтверждение, прежде чем убрать подрядчика', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
                const url = String(input);
                const method = init?.method ?? 'GET';
                if (url.endsWith('/organization/houses')) return json({ data: [house] });
                if (url.endsWith('/organization/executors')) return json({ data: [] });
                if (url.includes('/organization/contractors/4') && method === 'DELETE') {
                    return json({ data: { ok: true } });
                }
                if (url.endsWith('/organization/contractors')) {
                    return json({
                        data: [
                            {
                                id: 4,
                                type: 'intercom',
                                type_label: 'Домофонная компания',
                                name: 'Домофон-Сервис',
                                phone: null,
                            },
                        ],
                    });
                }
                if (url.endsWith('/organization')) return json({ data: organization });
                return json({ data: {} }, 404);
            }),
        );
        renderPage();
        fireEvent.click(await screen.findByRole('button', { name: 'Убрать' }));
        expect(screen.getByRole('dialog', { name: 'Убрать этого подрядчика?' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Да, убрать' }));
        await waitFor(() => {
            expect(
                vi
                    .mocked(fetch)
                    .mock.calls.some(
                        ([url, init]) =>
                            String(url).includes('/organization/contractors/4') &&
                            String(init?.method) === 'DELETE',
                    ),
            ).toBe(true);
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Подрядчик убран');
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
