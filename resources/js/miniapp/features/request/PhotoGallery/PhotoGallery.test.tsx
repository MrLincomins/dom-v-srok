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

        expect(screen.getByAltText('Фото после работы 1')).toBeInTheDocument();
        expect(screen.getByText('Ручку поменяли')).toBeInTheDocument();
        expect(screen.queryByText('Течёт кран')).not.toBeInTheDocument();
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
