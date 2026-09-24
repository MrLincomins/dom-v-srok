import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { openExternalLink } from '@/bridge/maxWebApp';
import { JournalPage } from '@/features/journal/JournalPage';

vi.mock('@/bridge/maxWebApp', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/bridge/maxWebApp')>();
    return {
        ...actual,
        openExternalLink: vi.fn(),
    };
});

const summary = {
    total: 8,
    open: 5,
    overdue: 1,
    closed: 2,
    on_time: 2,
    late: 0,
    returned: 0,
    redirected: 1,
    first_reaction_minutes: 4,
};

beforeEach(() => {
    vi.mocked(openExternalLink).mockClear();
    vi.stubGlobal(
        'fetch',
        vi.fn(async (input: RequestInfo | URL) => {
            const url = String(input);
            if (url.includes('/journal/csv-link')) {
                return json({ data: { url: 'https://example.test/journal.csv?signature=x' } });
            }
            if (url.includes('/journal')) {
                return json({
                    data: [
                        {
                            id: 12,
                            created_at: '2026-09-10T10:00:00Z',
                            address: 'ул. Мира, 12',
                            entrance: 1,
                            flat: '5',
                            resident_name: 'Житель Демо',
                            category: 'Лифт',
                            description: 'Скрипит',
                            responsible_name: 'ООО «Лифт-Сервис»',
                            basis: 'ПП РФ',
                            status: 'new',
                            status_label: 'Принято',
                            is_overdue: false,
                            closed_late: false,
                            returned_count: 0,
                            participants_count: 0,
                        },
                    ],
                    meta: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 30,
                        total: 1,
                        period: { from: '2026-09-01', to: '2026-09-21' },
                        summary,
                    },
                });
            }
            return json({ data: {} }, 404);
        }),
    );
});

describe('журнал заявок', () => {
    it('показывает сводку и строку за период', async () => {
        renderPage();
        expect(await screen.findByText('4 мин')).toBeInTheDocument();
        expect(screen.getByText(/№ 12/)).toBeInTheDocument();
        expect(screen.getByText(/Житель Демо/)).toBeInTheDocument();
        expect(
            vi
                .mocked(fetch)
                .mock.calls.some((call) =>
                    String(call[0]).includes('/journal?from=2026-09-01&to=2026-09-21'),
                ),
        ).toBe(true);
    });

    it('скачивает csv за выбранный период', async () => {
        renderPage();
        await screen.findByText(/№ 12/);
        fireEvent.click(screen.getByRole('button', { name: 'Скачать CSV' }));
        await waitFor(() => {
            expect(
                vi
                    .mocked(fetch)
                    .mock.calls.some((call) => String(call[0]).includes('/journal/csv-link?from=2026-09-01')),
            ).toBe(true);
        });
        expect(openExternalLink).toHaveBeenCalledWith('https://example.test/journal.csv?signature=x');
        expect(await screen.findByRole('status')).toHaveTextContent('CSV скачивается в браузере');
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
            <MemoryRouter initialEntries={['/journal?from=2026-09-01&to=2026-09-21']}>
                <QueryClientProvider client={client}>
                    <JournalPage />
                </QueryClientProvider>
            </MemoryRouter>
        </MaxUI>,
    );
}
