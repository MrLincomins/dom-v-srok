import { act, renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useDelayedFlag } from './useDelayedFlag';

describe('useDelayedFlag', () => {
    it('не включает флаг раньше 500 мс', () => {
        vi.useFakeTimers();
        const { result, rerender } = renderHook(({ active }) => useDelayedFlag(active, 500), {
            initialProps: { active: true },
        });
        expect(result.current).toBe(false);
        act(() => {
            vi.advanceTimersByTime(499);
        });
        expect(result.current).toBe(false);
        act(() => {
            vi.advanceTimersByTime(1);
        });
        expect(result.current).toBe(true);
        rerender({ active: false });
        expect(result.current).toBe(false);
        vi.useRealTimers();
    });
});
