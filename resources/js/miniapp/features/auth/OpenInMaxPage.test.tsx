import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { AuthProvider } from '@/app/auth';
import { useAuth } from '@/app/authContext';
import { OpenInMaxPage } from '@/features/auth/OpenInMaxPage';

function Probe() {
    const { status, user } = useAuth();
    return (
        <div>
            <span data-testid="status">{status}</span>
            <span data-testid="user">{user?.name ?? '-'}</span>
            {status === 'anonymous' && <OpenInMaxPage />}
        </div>
    );
}

describe('форма входа', () => {
    beforeEach(() => {
        document.head.innerHTML = '<meta name="demo-login" content="1">';
        sessionStorage.clear();
    });

    it('отправляет логин и пароль и переводит сессию в ready', async () => {
        const calls: Array<{ url: string; body: string }> = [];
        vi.stubGlobal('fetch', async (url: URL | string, init?: RequestInit) => {
            calls.push({ url: String(url), body: String(init?.body ?? '') });
            return new Response(
                JSON.stringify({ data: { token: 't', expires_at: '2026-09-18T00:00:00Z', user: { id: 1, name: 'Диспетчер Демо', role: 'dispatcher', is_demo: true } } }),
                { status: 200, headers: { 'Content-Type': 'application/json' } },
            );
        });
        const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
        render(
            <MaxUI platform="android" colorScheme="light">
                <QueryClientProvider client={client}>
                    <AuthProvider>
                        <Probe />
                    </AuthProvider>
                </QueryClientProvider>
            </MaxUI>,
        );
        await waitFor(() => expect(screen.getByTestId('status').textContent).toBe('anonymous'));
        fireEvent.change(screen.getByPlaceholderText('Логин'), { target: { value: 'demo_dispatcher' } });
        fireEvent.change(screen.getByPlaceholderText('Пароль'), { target: { value: 'secret' } });
        fireEvent.click(screen.getByRole('button', { name: 'Войти' }));
        await waitFor(() => expect(calls.some((c) => c.url.includes('/api/v1/auth/login'))).toBe(true), { timeout: 3000 });
        expect(calls.find((c) => c.url.includes('/auth/login'))?.body).toContain('demo_dispatcher');
        await waitFor(() => expect(screen.getByTestId('status').textContent).toBe('ready'), { timeout: 3000 });
        expect(screen.getByTestId('user').textContent).toBe('Диспетчер Демо');
    });
});
