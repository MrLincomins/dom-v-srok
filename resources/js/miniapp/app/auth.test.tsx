import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { AuthProvider } from './auth';
import { useAuth } from './authContext';

afterEach(() => {
    delete window.WebApp;
    vi.unstubAllGlobals();
});

describe('вход из MAX', () => {
    it('объясняет, что делать, и не показывает технический текст сервера', async () => {
        window.WebApp = { initData: 'query_id=1&hash=bad' };
        vi.stubGlobal(
            'fetch',
            vi.fn(
                async () =>
                    new Response(
                        JSON.stringify({
                            error: {
                                code: 'unauthenticated',
                                message: 'initData: подпись не совпала',
                                details: {},
                            },
                        }),
                        { status: 401, headers: { 'Content-Type': 'application/json' } },
                    ),
            ),
        );

        render(
            <QueryClientProvider client={new QueryClient()}>
                <AuthProvider>
                    <SessionProbe />
                </AuthProvider>
            </QueryClientProvider>,
        );

        expect(
            await screen.findByText('Не получилось войти. Закройте кабинет и откройте его из бота заново.'),
        ).toBeInTheDocument();
        expect(screen.queryByText(/initData/)).not.toBeInTheDocument();
    });
});

function SessionProbe() {
    const auth = useAuth();
    return auth.status === 'error' ? <p>{auth.error}</p> : null;
}
