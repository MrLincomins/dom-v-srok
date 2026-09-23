import { act, renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { DESKTOP_QUERY, TABLET_QUERY, useWideLayout } from './useWideLayout';

describe('useWideLayout', () => {
    it('считает экран узким, если matchMedia не совпал', () => {
        const { result } = renderHook(() => useWideLayout());
        expect(result.current).toBe(false);
        expect(TABLET_QUERY).toBe('(min-width: 768px)');
        expect(DESKTOP_QUERY).toBe('(min-width: 1024px)');
    });

    it('переключается при смене ширины', () => {
        const original = window.matchMedia;
        const listeners = new Set<() => void>();
        const media = {
            matches: false,
            media: TABLET_QUERY,
            addEventListener: (_event: string, listener: () => void) => {
                listeners.add(listener);
            },
            removeEventListener: (_event: string, listener: () => void) => {
                listeners.delete(listener);
            },
        };
        window.matchMedia = ((query: string) =>
            query === TABLET_QUERY ? media : { matches: false, media: query }) as typeof window.matchMedia;

        const { result } = renderHook(() => useWideLayout());
        expect(result.current).toBe(false);

        media.matches = true;
        act(() => {
            listeners.forEach((listener) => listener());
        });
        expect(result.current).toBe(true);
        window.matchMedia = original;
    });
});
