import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { PhotoGrid } from './PhotoGrid';
import { photosThatFit } from './usePhotosPerRow';

function thumbs(count: number) {
    return Array.from({ length: count }, (_, index) => (
        <img key={index} alt={`Фото ${index + 1}`} src={`https://example.test/${index}.jpg`} />
    ));
}

function stubContainerWidth(width: number) {
    vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(width);
}

afterEach(() => {
    vi.restoreAllMocks();
});

describe('сколько фото влезает в ряд', () => {
    it('считает по ширине контейнера, плитке и зазору', () => {
        expect(photosThatFit(304, 96, 8)).toBe(3);
        expect(photosThatFit(303, 96, 8)).toBe(2);
        expect(photosThatFit(408, 96, 8)).toBe(4);
        expect(photosThatFit(744, 180, 8)).toBe(4);
        expect(photosThatFit(0, 96, 8)).toBe(0);
    });
});

describe('сетка фото', () => {
    it('прячет фото, которые не влезли в ширину, и раскрывает по кнопке', () => {
        stubContainerWidth(304);
        render(<PhotoGrid>{thumbs(5)}</PhotoGrid>);

        expect(screen.getByAltText('Фото 1')).toBeInTheDocument();
        expect(screen.getByAltText('Фото 3')).toBeInTheDocument();
        expect(screen.queryByAltText('Фото 4')).not.toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Ещё 2 фото' }));
        expect(screen.getByAltText('Фото 5')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Скрыть' }));
        expect(screen.queryByAltText('Фото 4')).not.toBeInTheDocument();
    });

    it('в более широком контейнере оставляет в первом ряду больше фото', () => {
        stubContainerWidth(408);
        render(<PhotoGrid>{thumbs(5)}</PhotoGrid>);

        expect(screen.getByAltText('Фото 4')).toBeInTheDocument();
        expect(screen.queryByAltText('Фото 5')).not.toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Ещё 1 фото' })).toBeInTheDocument();
    });
});
