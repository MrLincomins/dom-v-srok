import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it, vi } from 'vitest';
import type { RequestCard } from '@/api/types';
import { StaffRequestActions } from '@/features/request/StaffRequestActions';

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

    it('показывает полные подписи полей переадресации', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);
                if (url.endsWith('/organization/executors')) {
                    return json({ data: [] });
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

        fireEvent.click(screen.getByRole('button', { name: 'Передать другой службе' }));

        expect(screen.getByLabelText('Какой службе передать')).toBeInTheDocument();
        expect(screen.getByLabelText('Телефон, если есть')).toBeInTheDocument();
        expect(screen.getByPlaceholderText('Что сказать жителю')).toBeInTheDocument();
    });

    it('передаёт заявку без телефона', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                const url = String(input);
                if (url.endsWith('/organization/executors')) {
                    return json({ data: [] });
                }
                if (url.includes('/requests/17/redirect')) {
                    return json({ data: { ...card, status: 'redirected' } });
                }
                return json({ data: card });
            }),
        );

        renderActions(card);
        fireEvent.click(screen.getByRole('button', { name: 'Передать другой службе' }));
        fireEvent.change(screen.getByLabelText('Какой службе передать'), {
            target: { value: 'Горсвет' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Передать заявку' }));

        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(([url, init]) => {
                    if (!String(url).includes('/requests/17/redirect')) return false;
                    const body = JSON.parse(String(init?.body ?? '{}')) as { name?: string; phone?: string };
                    return body.name === 'Горсвет' && body.phone === undefined;
                }),
            ).toBe(true);
        });
    });

    it('не требует телефон при передаче, но не принимает неполный номер', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                if (String(input).endsWith('/organization/executors')) {
                    return json({ data: [] });
                }
                return json({ data: card });
            }),
        );

        renderActions(card);
        fireEvent.click(screen.getByRole('button', { name: 'Передать другой службе' }));
        fireEvent.change(screen.getByLabelText('Какой службе передать'), {
            target: { value: 'Горсвет' },
        });
        fireEvent.change(screen.getByLabelText('Телефон, если есть'), {
            target: { value: '917' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Передать заявку' }));

        expect(await screen.findByRole('alert')).toHaveTextContent('Введите 10 цифр, без +7');
        expect(
            vi.mocked(fetch).mock.calls.some(([url]) => String(url).includes('/requests/17/redirect')),
        ).toBe(false);
    });

    it('подставляет шаблон в комментарий', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => json({ data: [] })),
        );

        const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
        render(
            <MaxUI platform="android" colorScheme="light">
                <QueryClientProvider client={client}>
                    <StaffRequestActions card={card} />
                </QueryClientProvider>
            </MaxUI>,
        );

        fireEvent.click(await screen.findByRole('button', { name: 'Выехал' }));
        expect(screen.getByPlaceholderText('Напишите, что сделали или что мешает')).toHaveValue('Выехал');
        expect(screen.queryByRole('button', { name: 'В работу' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Завершить' })).not.toBeInTheDocument();
    });

    it('у назначенной заявки показывает только «В работу»', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => json({ data: [] })));
        renderActions({
            ...card,
            status: 'assigned',
            allowed_transitions: ['in_progress', 'redirected'],
        });
        expect(await screen.findByRole('button', { name: 'В работу' })).toBeInTheDocument();
        expect(screen.queryByText('Назначить')).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Завершить' })).not.toBeInTheDocument();
    });

    it('показывает уведомление на 403 без текста сервера', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                if (String(input).endsWith('/organization/executors')) {
                    return json({ data: [{ id: 5, name: 'Иван', specialty: null, phone: null }] });
                }
                return new Response(JSON.stringify({ error: { message: 'Access denied raw' } }), {
                    status: 403,
                    headers: { 'Content-Type': 'application/json' },
                });
            }),
        );
        renderActions(card);
        fireEvent.click(await screen.findByText('Иван'));
        expect(await screen.findByRole('status')).toHaveTextContent('Нет доступа к этому действию.');
    });

    it('показывает уведомление, когда заявку берут в работу', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                if (String(input).includes('/requests/17/status')) {
                    return json({ data: { ...card, status: 'in_progress' } });
                }
                return json({ data: [] });
            }),
        );
        renderActions({
            ...card,
            status: 'assigned',
            allowed_transitions: ['in_progress', 'redirected'],
        });
        fireEvent.click(await screen.findByRole('button', { name: 'В работу' }));
        expect(await screen.findByRole('status')).toHaveTextContent('Заявка взята в работу');
        expect(screen.getByRole('status')).toHaveClass('is-success');
    });

    it('показывает уведомление, когда комментарий добавлен', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async (input: RequestInfo | URL) => {
                if (String(input).endsWith('/requests/17/comments')) {
                    return json({ data: card });
                }
                return json({ data: [] });
            }),
        );
        renderActions(card);
        fireEvent.click(await screen.findByRole('button', { name: 'Выехал' }));
        fireEvent.click(screen.getByRole('button', { name: 'Написать комментарий' }));
        expect(await screen.findByRole('status')).toHaveTextContent('Комментарий добавлен');
        expect(screen.getByRole('status')).toHaveClass('is-success');
    });

    it('открывает форму закрытия только по «Завершить»', async () => {
        vi.stubGlobal('fetch', vi.fn(async () => json({ data: [] })));
        renderActions({
            ...card,
            status: 'in_progress',
            allowed_transitions: ['done', 'redirected'],
        });
        expect(await screen.findByRole('button', { name: 'Завершить' })).toBeInTheDocument();
        const fold = document.querySelector('.section-fold');
        expect(fold).not.toHaveClass('is-open');
        expect(fold).toHaveAttribute('aria-hidden', 'true');
        fireEvent.click(screen.getByRole('button', { name: 'Завершить' }));
        expect(fold).toHaveClass('is-open');
        expect(fold).toHaveAttribute('aria-hidden', 'false');
        expect(screen.getByText('Работа закончена?')).toBeInTheDocument();
    });

    it('сохраняет комментарий и фото при повторном «Завершить»', async () => {
        const createObjectURL = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:finish-photo');
        vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);
        vi.stubGlobal('fetch', vi.fn(async () => json({ data: [] })));
        renderActions({
            ...card,
            status: 'in_progress',
            allowed_transitions: ['done', 'redirected'],
        });

        fireEvent.click(await screen.findByRole('button', { name: 'Завершить' }));
        fireEvent.change(screen.getByPlaceholderText('Напишите, что сделали'), {
            target: { value: 'Протечку устранили' },
        });
        fireEvent.change(document.querySelector('input[type="file"]')!, {
            target: { files: [new File(['img'], 'leak.jpg', { type: 'image/jpeg' })] },
        });
        expect(screen.getByAltText('leak.jpg')).toHaveAttribute('src', 'blob:finish-photo');

        const fold = document.querySelector('.section-fold');
        fireEvent.click(screen.getByRole('button', { name: 'Завершить' }));
        expect(fold).not.toHaveClass('is-open');

        fireEvent.click(screen.getByRole('button', { name: 'Завершить' }));
        expect(fold).toHaveClass('is-open');
        expect(screen.getByPlaceholderText('Напишите, что сделали')).toHaveValue('Протечку устранили');
        expect(screen.getByAltText('leak.jpg')).toHaveAttribute('src', 'blob:finish-photo');
        vi.restoreAllMocks();
    });
});

function renderActions(value: RequestCard) {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
    render(
        <MaxUI platform="android" colorScheme="light">
            <QueryClientProvider client={client}>
                <StaffRequestActions card={value} />
            </QueryClientProvider>
        </MaxUI>,
    );
}

function json(body: unknown, status = 200) {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}
