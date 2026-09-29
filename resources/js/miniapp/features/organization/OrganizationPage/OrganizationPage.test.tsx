import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter, Route, Routes } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import { OrganizationPage, OrganizationSectionPage } from '@/features/organization/OrganizationPage';

const organization = {
    id: 1,
    name: 'Мир',
    type: 'tsj',
    phone_ads: '+78430000001',
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
                return json({ data: { id: 6, name: 'Пётр', specialty: null, phone: '+79000000013' } });
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
                            phone: '+79000000013',
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
    it('даёт выйти из демо-учётки', async () => {
        const logout = vi.fn().mockResolvedValue(undefined);
        renderPage('/organization', logout);
        fireEvent.click(await screen.findByRole('button', { name: 'Выйти' }));
        expect(logout).toHaveBeenCalledOnce();
    });

    it('открывает разделы с хаба', async () => {
        renderPage('/organization');
        expect(await screen.findByText('Журнал заявок')).toBeInTheDocument();
        fireEvent.click(screen.getByText('Контакты'));
        expect(await screen.findByDisplayValue('Мир')).toBeInTheDocument();
    });

    it('включает присоединение в чате на экране домов', async () => {
        renderPage('/organization/houses');

        fireEvent.click(await screen.findByText('Предлагать присоединиться'));

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
    });

    it('просит подтверждение, прежде чем убрать мастера', async () => {
        renderPage('/organization/executors');
        fireEvent.click(await screen.findByRole('button', { name: 'Убрать' }));
        expect(screen.getByRole('dialog', { name: 'Убрать этого мастера?' })).toBeInTheDocument();
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
        expect(await screen.findByRole('status')).toHaveTextContent('Мастер убран');
    });

    it('ставит фокус на первое поле с ошибкой', async () => {
        renderPage('/organization/contacts');
        const name = await screen.findByLabelText('Название');
        fireEvent.change(name, { target: { value: '' } });
        fireEvent.change(screen.getByLabelText('Почта'), { target: { value: 'плохо' } });
        fireEvent.click(screen.getByRole('button', { name: 'Сохранить' }));
        expect(name).toBeInvalid();
        expect(name).toHaveFocus();
        expect(screen.getByLabelText('Почта')).not.toHaveFocus();
    });

    it('подсвечивает почту и объясняет ошибку без запроса', async () => {
        renderPage('/organization/contacts');
        const email = await screen.findByLabelText('Почта');
        fireEvent.change(email, { target: { value: 'demo@example.ruффафыафы' } });
        fireEvent.click(screen.getByRole('button', { name: 'Сохранить' }));
        expect(email).toBeInvalid();
        expect(email).toHaveFocus();
        expect(await screen.findByRole('status')).toHaveTextContent('Укажите правильную почту');
        expect(
            vi
                .mocked(fetch)
                .mock.calls.some(
                    ([url, init]) =>
                        String(url).endsWith('/organization') && String(init?.method) === 'PATCH',
                ),
        ).toBe(false);
    });

    it('не даёт сохранить контакты без изменений', async () => {
        renderPage('/organization/contacts');
        expect(await screen.findByRole('button', { name: 'Сохранить' })).toBeDisabled();
    });

    it('сохраняет контакты и показывает уведомление', async () => {
        renderPage('/organization/contacts');
        fireEvent.change(await screen.findByLabelText('Часы приёма'), { target: { value: '9:00–18:00' } });
        fireEvent.click(screen.getByRole('button', { name: 'Сохранить' }));
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

    it('не даёт добавить мастера из пустой формы', async () => {
        renderPage('/organization/executors');
        expect(await screen.findByRole('button', { name: 'Добавить мастера' })).toBeDisabled();
    });

    it('не добавляет мастера без телефона', async () => {
        renderPage('/organization/executors');
        const form = (await screen.findByRole('button', { name: 'Добавить мастера' })).closest('form');
        expect(form).toBeTruthy();
        fireEvent.change(within(form as HTMLElement).getByLabelText('Имя'), { target: { value: 'Пётр' } });
        fireEvent.click(screen.getByRole('button', { name: 'Добавить мастера' }));
        expect(await screen.findByRole('alert')).toHaveTextContent('Укажите номер телефона');
        expect(screen.getByRole('status')).toHaveTextContent('Укажите номер телефона');
        expect(
            vi
                .mocked(fetch)
                .mock.calls.some(
                    ([url, init]) =>
                        String(url).endsWith('/organization/executors') && String(init?.method) === 'POST',
                ),
        ).toBe(false);
    });

    it('принимает телефон мастера без +7', async () => {
        renderPage('/organization/executors');
        const form = (await screen.findByRole('button', { name: 'Добавить мастера' })).closest('form');
        expect(form).toBeTruthy();
        const name = within(form as HTMLElement).getByLabelText('Имя');
        const phone = within(form as HTMLElement).getByLabelText('Телефон');
        fireEvent.change(name, { target: { value: 'Пётр' } });
        fireEvent.change(phone, { target: { value: '9000000013' } });
        fireEvent.click(screen.getByRole('button', { name: 'Добавить мастера' }));
        await waitFor(() => {
            const call = vi
                .mocked(fetch)
                .mock.calls.find(
                    ([url, init]) =>
                        String(url).endsWith('/organization/executors') && String(init?.method) === 'POST',
                );
            expect(call?.[1]?.body).toContain('+79000000013');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Мастер добавлен');
    });

    it('копирует ссылку для жителя из списка домов', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.assign(navigator, { clipboard: { writeText } });
        renderPage('/organization/houses');
        fireEvent.click(await screen.findByText('Ссылка для жителя'));
        await waitFor(() => {
            expect(writeText).toHaveBeenCalledWith('https://max.ru/start');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Скопировано');
        expect(screen.getAllByText('Скопировано').length).toBeGreaterThan(0);
    });

    it('показывает QR дома', async () => {
        renderPage('/organization/houses');
        const qr = await screen.findByRole('img', { name: 'QR-код дома' });
        expect(qr).toHaveAttribute('src', 'https://max.example/qr.png?signature=x');
        fireEvent.click(screen.getByRole('button', { name: '2' }));
        expect(screen.getByRole('img', { name: 'QR-код дома' })).toHaveAttribute(
            'src',
            'https://max.example/qr.png?signature=x&entrance=2',
        );
        expect(screen.getByText('Скачать QR')).toBeInTheDocument();
    });

    it('не даёт добавить подрядчика из пустой формы', async () => {
        renderPage('/organization/contractors');
        expect(await screen.findByRole('button', { name: 'Добавить подрядчика' })).toBeDisabled();
    });

    it('не добавляет подрядчика без телефона', async () => {
        renderPage('/organization/contractors');
        const form = (await screen.findByRole('button', { name: 'Добавить подрядчика' })).closest('form');
        expect(form).toBeTruthy();
        fireEvent.change(within(form as HTMLElement).getByLabelText('Название'), {
            target: { value: 'Лифт-Сервис' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Добавить подрядчика' }));
        expect(await screen.findByRole('alert')).toHaveTextContent('Укажите номер телефона');
        expect(screen.getByRole('status')).toHaveTextContent('Укажите номер телефона');
        expect(
            vi
                .mocked(fetch)
                .mock.calls.some(
                    ([url, init]) =>
                        String(url).endsWith('/organization/contractors') && String(init?.method) === 'POST',
                ),
        ).toBe(false);
    });

    it('добавляет подрядчика', async () => {
        renderPage('/organization/contractors');
        const form = (await screen.findByRole('button', { name: 'Добавить подрядчика' })).closest('form');
        expect(form).toBeTruthy();
        fireEvent.click(within(form as HTMLElement).getByRole('button', { name: 'Лифт' }));
        fireEvent.change(within(form as HTMLElement).getByLabelText('Название'), {
            target: { value: 'Лифт-Сервис' },
        });
        fireEvent.change(within(form as HTMLElement).getByLabelText('Телефон'), {
            target: { value: '9000000013' },
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
            expect(call?.[1]?.body).toContain('+79000000013');
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
                if (url.endsWith('/organization')) return json({ data: organization });
                return json({ data: {} }, 404);
            }),
        );
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.assign(navigator, { clipboard: { writeText } });
        renderPage('/organization/houses');
        fireEvent.click(await screen.findByText('Как привязать чат'));
        await waitFor(() => {
            expect(writeText).toHaveBeenCalledWith('/дом qr');
        });
        expect(await screen.findByRole('status')).toHaveTextContent('Скопировано');
    });

    it('просит подтверждение, прежде чем убрать подрядчика', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
                const url = String(input);
                const method = init?.method ?? 'GET';
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
                return json({ data: {} }, 404);
            }),
        );
        renderPage('/organization/contractors');
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

    it('объясняет ошибку загрузки мастеров и загружает список снова', async () => {
        let failed = false;
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                if (String(input).endsWith('/organization/executors')) {
                    if (!failed) {
                        failed = true;
                        return json({ error: { code: 'server_error', message: 'boom', details: {} } }, 500);
                    }
                    return json({ data: [{ id: 5, name: 'Иван', specialty: null, phone: null }] });
                }
                return json({ data: {} }, 404);
            }),
        );
        renderPage('/organization/executors');

        expect(await screen.findByRole('alert')).toHaveTextContent('Сервер не ответил. Попробуйте ещё раз.');
        expect(screen.queryByText('Мастеров пока нет.')).not.toBeInTheDocument();
        const retry = screen.getByRole('button', { name: 'Повторить' });
        expect(retry).toHaveAttribute('type', 'button');
        fireEvent.click(retry);

        expect(await screen.findByText('Иван')).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('показывает загрузку, пока список подрядчиков не пришёл', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() => new Promise<Response>(() => undefined)),
        );
        renderPage('/organization/contractors');

        expect(await screen.findByRole('status', { name: 'Загрузка…' })).toBeInTheDocument();
        expect(screen.queryByText('Подрядчиков пока нет.')).not.toBeInTheDocument();
    });

    it('объясняет ошибку загрузки подрядчиков и загружает список снова', async () => {
        let failed = false;
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                if (String(input).endsWith('/organization/contractors')) {
                    if (!failed) {
                        failed = true;
                        return json({ error: { code: 'server_error', message: 'boom', details: {} } }, 500);
                    }
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
                return json({ data: {} }, 404);
            }),
        );
        renderPage('/organization/contractors');

        expect(await screen.findByRole('alert')).toHaveTextContent('Сервер не ответил. Попробуйте ещё раз.');
        fireEvent.click(screen.getByRole('button', { name: 'Повторить' }));

        expect(await screen.findByText('Домофон-Сервис')).toBeInTheDocument();
    });
});

function json(body: unknown, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}

function renderPage(path: string, logout = vi.fn()) {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    const auth: AuthState = {
        status: 'ready',
        user: { id: 1, name: 'Диспетчер', role: 'dispatcher', is_demo: true },
        error: null,
        loginDemo: vi.fn(),
        logout,
        refresh: vi.fn(),
    };
    return render(
        <MaxUI platform="android" colorScheme="light">
            <MemoryRouter initialEntries={[path]}>
                <QueryClientProvider client={client}>
                    <AuthContext.Provider value={auth}>
                        <Routes>
                            <Route path="/organization" element={<OrganizationPage />} />
                            <Route path="/organization/:section" element={<OrganizationSectionPage />} />
                        </Routes>
                    </AuthContext.Provider>
                </QueryClientProvider>
            </MemoryRouter>
        </MaxUI>,
    );
}
