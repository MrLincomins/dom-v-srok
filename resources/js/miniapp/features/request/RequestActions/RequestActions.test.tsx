import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it } from 'vitest';
import type { RequestCard, RequestStatus } from '@/api/types';
import { RequestActions } from './RequestActions';

describe('действия жителя на карточке', () => {
    it('показывает «да» и «нет» автору выполненной заявки', () => {
        renderActions(['confirmed', 'returned']);

        expect(screen.getByRole('button', { name: 'Да, всё хорошо' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Нет, ещё не готово' })).toBeInTheDocument();
    });

    it('не показывает кнопки соседу, которому сервер не разрешил подтверждать', () => {
        renderActions([]);

        expect(screen.queryByRole('button', { name: 'Да, всё хорошо' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Нет, ещё не готово' })).not.toBeInTheDocument();
        expect(screen.queryByText('Проблему решили?')).not.toBeInTheDocument();
    });

    it('показывает только разрешённый ответ', () => {
        renderActions(['confirmed']);

        expect(screen.getByRole('button', { name: 'Да, всё хорошо' })).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Нет, ещё не готово' })).not.toBeInTheDocument();
    });
});

function renderActions(allowed: RequestStatus[]) {
    const card = { id: 7, status: 'done', allowed_transitions: allowed } as RequestCard;
    return render(
        <MaxUI platform="android" colorScheme="light">
            <QueryClientProvider client={new QueryClient()}>
                <RequestActions card={card} isStaff={false} />
            </QueryClientProvider>
        </MaxUI>,
    );
}
