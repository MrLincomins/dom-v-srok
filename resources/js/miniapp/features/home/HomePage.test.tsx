import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import type { User } from '@/api/types';
import { HomePage } from './HomePage';

const baseUser: User = {
    id: 1,
    name: 'Тестовый пользователь',
    role: 'dispatcher',
    is_demo: true,
};

beforeEach(() => {
    vi.stubGlobal(
        'fetch',
        vi.fn(
            async () =>
                new Response(
                    JSON.stringify({
                        data: [],
                        meta: {
                            current_page: 1,
                            last_page: 1,
                            per_page: 1,
                            total: 0,
                            counters: { new: 2, in_progress: 3, overdue: 1, closed: 4 },
                        },
                    }),
                    { status: 200, headers: { 'Content-Type': 'application/json' } },
                ),
        ),
    );
});

describe('главный экран по роли', () => {
    it('показывает диспетчеру очередь заявок', async () => {
        renderHome({ ...baseUser, role: 'dispatcher' });

        expect(await screen.findByRole('heading', { name: 'Заявки' })).toBeInTheDocument();
        expect(screen.getByText('Просрочено')).toBeInTheDocument();
        expect(screen.getByLabelText('Организация')).toBeInTheDocument();
        expect(screen.queryByText('Выйти')).not.toBeInTheDocument();
    });

    it('показывает жителю только свои заявки', async () => {
        renderHome({ ...baseUser, role: 'resident' });

        expect(await screen.findByRole('heading', { name: 'Мои заявки' })).toBeInTheDocument();
        expect(screen.queryByText('Сообщить о проблеме')).not.toBeInTheDocument();
        expect(screen.queryByText('Выйти')).not.toBeInTheDocument();
    });
});

function renderHome(user: User) {
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
                        <HomePage />
                    </AuthContext.Provider>
                </QueryClientProvider>
            </MemoryRouter>
        </MaxUI>,
    );
}
