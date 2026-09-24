import { render, screen } from '@testing-library/react';
import { MaxUI } from '@maxhub/max-ui';
import { MemoryRouter } from 'react-router';
import { describe, expect, it, vi } from 'vitest';
import { AuthContext, type AuthState } from '@/app/authContext';
import type { User } from '@/api/types';
import { HousePage } from '@/features/resident/HousePage';

const user: User = {
    id: 2,
    name: 'Житель Демо',
    role: 'resident',
    is_demo: true,
    entrance: 2,
    flat: '45',
    organization: {
        id: 1,
        name: 'ТСЖ «Демо»',
        type: 'tsj',
        phone_ads: '+7 843 000-00-01',
        phone_dispatch: '+7 843 000-00-02',
        email: 'demo@example.ru',
        reception_hours: 'пн–пт 9:00–18:00',
        reception_address: 'Казань, ул. Демонстрационная, д. 1, офис ТСЖ',
        direct_contracts: {
            cold_water: false,
            hot_water: false,
            heat: false,
            power: false,
            tko: false,
        },
        is_demo: true,
    },
    house: {
        id: 1,
        address: 'Казань, ул. Демонстрационная, д. 1',
        entrances: 3,
        qr_token: 'qr',
        chat_bound: true,
        chat_keywords_enabled: false,
        chat_pinned: false,
        qr_url: 'https://max.ru/qr.png',
        start_url: 'https://max.ru/start',
    },
};

describe('дом жителя', () => {
    it('показывает адрес, телефоны и организацию', () => {
        renderHouse();

        expect(screen.getByRole('heading', { name: 'Дом' })).toBeInTheDocument();
        expect(screen.getByText('Демонстрационная, 1')).toBeInTheDocument();
        expect(screen.getByText('Подъезд')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /Аварийная служба/ })).toHaveAttribute(
            'href',
            'tel:+78430000001',
        );
        expect(screen.getByRole('link', { name: /Диспетчерская/ })).toHaveAttribute(
            'href',
            'tel:+78430000002',
        );
        expect(screen.getByText('ТСЖ «Демо»')).toBeInTheDocument();
        expect(screen.getByText('пн–пт 9:00–18:00')).toBeInTheDocument();
        expect(screen.getByText('Почта')).toBeInTheDocument();
        expect(screen.getByText('demo@example.ru')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Выйти' })).toBeInTheDocument();
    });
});

function renderHouse() {
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
                <AuthContext.Provider value={auth}>
                    <HousePage />
                </AuthContext.Provider>
            </MemoryRouter>
        </MaxUI>,
    );
}
