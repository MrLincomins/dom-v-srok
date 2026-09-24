import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it, vi } from 'vitest';
import { ResidentRequestActions } from './ResidentRequestActions';

describe('подтверждение жителем', () => {
    it('отправляет комментарий вместе с «да, всё хорошо»', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => json({ data: { id: 7, status: 'confirmed' } })),
        );

        render(
            <MaxUI platform="android" colorScheme="dark">
                <QueryClientProvider
                    client={new QueryClient({ defaultOptions: { mutations: { retry: false } } })}
                >
                    <ResidentRequestActions requestId={7} />
                </QueryClientProvider>
            </MaxUI>,
        );

        fireEvent.change(screen.getByPlaceholderText('Напишите, что ещё не так'), {
            target: { value: 'Спасибо, всё работает' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Да, всё хорошо' }));

        await waitFor(() => {
            expect(
                vi.mocked(fetch).mock.calls.some(
                    ([url, init]) =>
                        String(url).includes('/requests/7/confirm') &&
                        String(init?.body).includes('"resolved":true') &&
                        String(init?.body).includes('Спасибо, всё работает'),
                ),
            ).toBe(true);
        });
    });
});

function json(body: unknown) {
    return new Response(JSON.stringify(body), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
    });
}
