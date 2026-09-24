import { render, screen } from '@testing-library/react';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it } from 'vitest';
import type { Attachment, RequestEvent } from '@/api/types';
import { PhotoGallery } from './PhotoGallery';

describe('фотографии заявки', () => {
    it('показывает комментарий жителя вместе с фото', () => {
        renderGallery(
            [
                {
                    id: 1,
                    kind: 'resident',
                    mime: 'image/jpeg',
                    url: 'https://example.test/stove.jpg',
                    created_at: '2026-09-24T02:39:00Z',
                },
            ],
            [event({ id: 1, type: 'created', comment: 'Не крутится ручка плиты' })],
        );

        expect(screen.getByText('Фото жителя')).toBeInTheDocument();
        expect(screen.getByText('Комментарий')).toBeInTheDocument();
        expect(screen.getByAltText('Фото жителя 1')).toHaveAttribute(
            'src',
            'https://example.test/stove.jpg',
        );
        expect(screen.getByText('Не крутится ручка плиты')).toBeInTheDocument();
    });

    it('показывает комментарий к фото после работы', () => {
        renderGallery(
            [
                {
                    id: 2,
                    kind: 'closing',
                    mime: 'image/jpeg',
                    url: 'https://example.test/done.jpg',
                    created_at: '2026-09-24T03:00:00Z',
                },
            ],
            [
                event({
                    id: 4,
                    type: 'status_changed',
                    to_status: 'done',
                    comment: 'Ручку поменяли',
                }),
            ],
            'Течёт кран',
        );

        expect(screen.getByText('Фото после работы')).toBeInTheDocument();
        expect(screen.getByText('Комментарий')).toBeInTheDocument();
        expect(screen.getByAltText('Фото после работы 1')).toBeInTheDocument();
        expect(screen.getByText('Ручку поменяли')).toBeInTheDocument();
        expect(screen.queryByText('Течёт кран')).not.toBeInTheDocument();
    });

    it('не склеивает фото двух закрытий в один блок', () => {
        renderGallery(
            [
                {
                    id: 11,
                    kind: 'closing',
                    mime: 'image/jpeg',
                    url: 'https://example.test/first.jpg',
                    created_at: '2026-09-24T09:00:50Z',
                },
                {
                    id: 12,
                    kind: 'closing',
                    mime: 'image/jpeg',
                    url: 'https://example.test/second.jpg',
                    created_at: '2026-09-24T09:08:50Z',
                },
            ],
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
                    type: 'status_changed',
                    to_status: 'done',
                    comment: 'Второй раз',
                    created_at: '2026-09-24T09:09:00Z',
                }),
            ],
        );

        expect(screen.getByText('Фото после работы, 1-й раз')).toBeInTheDocument();
        expect(screen.getByText('Фото после работы, 2-й раз')).toBeInTheDocument();
        expect(screen.getAllByText('Комментарий')).toHaveLength(2);
        expect(screen.getByText('Первый раз')).toBeInTheDocument();
        expect(screen.getByText('Второй раз')).toBeInTheDocument();
        const first = screen.getByText('Первый раз').closest('.photo-with-comment');
        const second = screen.getByText('Второй раз').closest('.photo-with-comment');
        expect(first?.querySelector('img[src="https://example.test/first.jpg"]')).toBeTruthy();
        expect(first?.querySelector('img[src="https://example.test/second.jpg"]')).toBeFalsy();
        expect(second?.querySelector('img[src="https://example.test/second.jpg"]')).toBeTruthy();
        expect(second?.querySelector('img[src="https://example.test/first.jpg"]')).toBeFalsy();
    });
});

function event(partial: Partial<RequestEvent> & Pick<RequestEvent, 'id' | 'type'>): RequestEvent {
    return {
        actor_role: 'dispatcher',
        created_at: '2026-09-24T02:39:00Z',
        ...partial,
    };
}

function renderGallery(attachments: Attachment[], events: RequestEvent[], description?: string) {
    return render(
        <MaxUI platform="android" colorScheme="dark">
            <PhotoGallery attachments={attachments} events={events} description={description} />
        </MaxUI>,
    );
}
