import { useLayoutEffect, useState, type RefObject } from 'react';

const DEFAULT_TILE = 96;
const DEFAULT_GAP = 8;

/** Сколько плиток встаёт в ряд: floor((ширина + зазор) / (плитка + зазор)). */
export function photosThatFit(containerWidth: number, tile: number, gap: number): number {
    if (containerWidth <= 0 || tile <= 0) return 0;
    return Math.max(1, Math.floor((containerWidth + gap) / (tile + gap)));
}

function readSize(node: HTMLElement): { width: number; tile: number; gap: number } {
    const styles = getComputedStyle(node);
    const tile = parseFloat(styles.getPropertyValue('--photo-tile')) || DEFAULT_TILE;
    const gap = parseFloat(styles.getPropertyValue('--photo-gap')) || DEFAULT_GAP;
    return { width: node.clientWidth, tile, gap };
}

export function usePhotosPerRow(container: RefObject<HTMLElement | null>): number {
    const [perRow, setPerRow] = useState(0);

    useLayoutEffect(() => {
        const node = container.current;
        if (!node) return;

        const update = () => {
            const { width, tile, gap } = readSize(node);
            setPerRow(photosThatFit(width, tile, gap));
        };

        update();
        if (typeof ResizeObserver === 'undefined') return;
        const observer = new ResizeObserver(update);
        observer.observe(node);
        return () => observer.disconnect();
    }, [container]);

    return perRow;
}
