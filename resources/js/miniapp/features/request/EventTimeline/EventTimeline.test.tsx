import { render, screen } from '@testing-library/react';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it } from 'vitest';
import type { Attachment, RequestEvent } from '@/api/types';
import { EventTimeline } from './EventTimeline';

describe('лента событий', () => {
    it('прячет дубль «Назначено», если уже есть исполнитель', () => {
        renderTimeline([
            event({ id: 1, type: 'created' }),
            event({ id: 2, type: 'assigned', payload: { executor: 'Сантехник Иванов' } }),
            event({ id: 3, type: 'status_changed', to_status: 'assigned' }),
            event({ id: 4, type: 'status_changed', to_status: 'in_progress' }),
        ]);

        expect(screen.getByText('Назначен исполнитель: Сантехник Иванов')).toBeInTheDocument();
        expect(screen.queryByText('Назначено')).not.toBeInTheDocument();
        expect(screen.getByText('В работе')).toBeInTheDocument();
    });

    it('оставляет «Назначено», если отдельного события назначения нет', () => {
        renderTimeline([
            event({ id: 1, type: 'created' }),
            event({ id: 2, type: 'status_changed', to_status: 'assigned' }),
        ]);

        expect(screen.getByText('Назначено')).toBeInTheDocument();
    });

    it('показывает комментарий отдельной строкой', () => {
        renderTimeline([
            event({
                id: 1,
                type: 'status_changed',
                to_status: 'done',
                comment: 'Доводчик заменён',
            }),
            event({ id: 2, type: 'comment', comment: 'zbs' }),
            event({
                id: 3,
                type: 'status_changed',
                to_status: 'confirmed',
                comment: 'Всё хорошо',
            }),
        ]);

        expect(screen.getByText('Доводчик заменён')).toBeInTheDocument();
        expect(screen.getByText('zbs')).toBeInTheDocument();
        expect(screen.getByText('Всё хорошо')).toBeInTheDocument();
        expect(screen.getByText('Подтверждено')).toBeInTheDocument();
    });

    it('не смешивает фото двух закрытий', () => {
        renderTimeline(
            [
                event({
                    id: 1,
                    type: 'status_changed',
                    to_status: 'done',
                    comment: 'Первый раз',
                    created_at: '2026-09-24T09:01:00Z',
                }),
                event({
                    id: 2,
                    type: 'returned',
                    created_at: '2026-09-24T09:03:00Z',
                }),
                event({
                    id: 3,
                    type: 'status_changed',
                    to_status: 'done',
                    comment: 'Второй раз',
                    created_at: '2026-09-24T09:09:00Z',
                }),
            ],
            [
                photo(11, 'https://example.test/a.jpg', '2026-09-24T09:00:50Z'),
                photo(12, 'https://example.test/b.jpg', '2026-09-24T09:00:51Z'),
                photo(13, 'https://example.test/c.jpg', '2026-09-24T09:08:50Z'),
            ],
        );

        const first = screen.getByText('Первый раз').closest('.history-body');
        const second = screen.getByText('Второй раз').closest('.history-body');
        expect(first?.querySelector('img[src="https://example.test/a.jpg"]')).toBeTruthy();
        expect(first?.querySelector('img[src="https://example.test/b.jpg"]')).toBeTruthy();
        expect(first?.querySelector('img[src="https://example.test/c.jpg"]')).toBeFalsy();
        expect(second?.querySelector('img[src="https://example.test/c.jpg"]')).toBeTruthy();
        expect(second?.querySelector('img[src="https://example.test/a.jpg"]')).toBeFalsy();
        expect(second?.querySelector('img[src="https://example.test/b.jpg"]')).toBeFalsy();
    });

    it('показывает фото оператора у «Выполнено»', () => {
        renderTimeline(
            [event({ id: 1, type: 'status_changed', to_status: 'done', comment: 'Готово' })],
            [
                {
                    id: 9,
                    kind: 'closing',
                    mime: 'image/jpeg',
                    url: 'https://example.test/done.jpg',
                    created_at: '2026-09-24T02:39:00Z',
                },
            ],
        );

        expect(screen.getByAltText('Фото после работы 1')).toHaveAttribute(
            'src',
            'https://example.test/done.jpg',
        );
    });

    it('красит все точки по шагу, а не только последнюю', () => {
        const { container } = renderTimeline([
            event({ id: 1, type: 'created' }),
            event({ id: 2, type: 'status_changed', to_status: 'in_progress' }),
            event({ id: 3, type: 'status_changed', to_status: 'done' }),
        ]);

        const dots = container.querySelectorAll('.history-dot');
        expect(dots[0]).toHaveClass('is-accepted');
        expect(dots[1]).toHaveClass('is-progress');
        expect(dots[2]).toHaveClass('is-ready');
        expect(container.querySelector('.history-title.is-progress')).toHaveTextContent('В работе');
    });
});

function photo(id: number, url: string, created_at: string): Attachment {
    return { id, kind: 'closing', mime: 'image/jpeg', url, created_at };
}

function event(partial: Partial<RequestEvent> & Pick<RequestEvent, 'id' | 'type'>): RequestEvent {
    return {
        actor_role: 'dispatcher',
        created_at: '2026-09-24T02:39:00Z',
        ...partial,
    };
}

function renderTimeline(events: RequestEvent[], attachments?: Attachment[]) {
    return render(
        <MaxUI platform="android" colorScheme="dark">
            <EventTimeline events={events} attachments={attachments} />
        </MaxUI>,
    );
}
