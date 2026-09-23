import { fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import type { User } from '@/api/types';
import { HomePage } from '@/features/home/HomePage';

const organization = {
    id: 1,
    name: 'ТСЖ «Демо»',
    type: 'tsj' as const,
    phone_ads: '+7 843 000-00-01',
    phone_dispatch: '+7 843 000-00-02',
    email: 'demo@example.ru',
    reception_hours: 'пн–пт 9:00–18:00',
    reception_address: 'ул. Мира, 12, офис',
    direct_contracts: {
        cold_water: false,
        hot_water: false,
        heat: false,
        power: false,
        tko: false,
    },
    is_demo: true,
};

const house = {
    id: 1,
    address: 'ул. Мира, 12',
    entrances: 2,
    qr_token: 'qr',
    chat_bound: true,
    chat_keywords_enabled: false,
    chat_pinned: false,
    qr_url: 'https://max.ru/qr.png',
    start_url: 'https://max.ru/start',
};

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
        expect(screen.getByRole('button', { name: 'Организация' })).toBeInTheDocument();
        expect(screen.queryByText('Выйти')).not.toBeInTheDocument();
    });

    it('показывает жителю кабинет со списком заявок', async () => {
        renderHome({
            ...baseUser,
            role: 'resident',
            entrance: 2,
            flat: '45',
            organization,
            house,
        });

        const open = vi.spyOn(window, 'open').mockImplementation(() => null);

        expect(await screen.findByRole('heading', { name: 'Кабинет' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Текущие' })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: 'Прошлые' })).toBeInTheDocument();
        expect(await screen.findByText('Открытых заявок нет.')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /Мои заявки/ })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /Прошлые заявки/ })).not.toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: /Сообщить о проблеме/ }));
        expect(open).toHaveBeenCalledWith('https://max.ru/start', '_blank', 'noopener,noreferrer');
        expect(screen.queryByText('Тестовый пользователь')).not.toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: /Дом и контакты/ }));
        const sheet = screen.getByRole('dialog', { name: 'Дом и контакты' });
        expect(within(sheet).getByText('Тестовый пользователь')).toBeInTheDocument();
        expect(within(sheet).getAllByText('Мира, 12').length).toBeGreaterThan(0);
        expect(within(sheet).getByText('Подъезд 2 · Квартира 45')).toBeInTheDocument();
        expect(within(sheet).getByText('ТСЖ «Демо»')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Закрыть' }));
        await waitFor(() => {
            expect(screen.queryByRole('dialog', { name: 'Дом и контакты' })).not.toBeInTheDocument();
        });
        expect(screen.queryByText('Выйти')).not.toBeInTheDocument();
    });

    it('закрывает sheet свайпом вниз от полоски', async () => {
        renderHome({
            ...baseUser,
            role: 'resident',
            entrance: 2,
            flat: '45',
            organization,
            house,
        });

        expect(await screen.findByRole('heading', { name: 'Кабинет' })).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: /Дом и контакты/ }));
        const grab = screen.getByRole('button', { name: 'Закрыть' });
        fireEvent.pointerDown(grab, { clientX: 120, clientY: 40, pointerId: 1 });
        fireEvent.pointerMove(grab, { clientX: 120, clientY: 130, pointerId: 1 });
        fireEvent.pointerUp(grab, { clientX: 120, clientY: 130, pointerId: 1 });
        await waitFor(() => {
            expect(screen.queryByRole('dialog', { name: 'Дом и контакты' })).not.toBeInTheDocument();
        });
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
