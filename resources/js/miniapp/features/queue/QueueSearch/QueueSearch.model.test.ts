import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { SEARCH_DEBOUNCE_MS, useQueueSearch } from './QueueSearch.model';

describe('useQueueSearch', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('применяет поиск после паузы в наборе', () => {
        const onSearch = vi.fn();
        const { result } = renderHook(() => useQueueSearch('', onSearch));
        act(() => result.current.setValue('  кровля '));
        act(() => vi.advanceTimersByTime(SEARCH_DEBOUNCE_MS - 1));
        expect(onSearch).not.toHaveBeenCalled();
        act(() => vi.advanceTimersByTime(1));
        expect(onSearch).toHaveBeenCalledWith('кровля');
    });

    it('по Enter применяет сразу', () => {
        const onSearch = vi.fn();
        const { result } = renderHook(() => useQueueSearch('', onSearch));
        act(() => result.current.setValue('№5'));
        act(() => result.current.submit());
        expect(onSearch).toHaveBeenCalledWith('№5');
    });

    it('не повторяет уже применённый поиск', () => {
        const onSearch = vi.fn();
        const { result } = renderHook(() => useQueueSearch('мира', onSearch));
        expect(result.current.value).toBe('мира');
        act(() => vi.advanceTimersByTime(SEARCH_DEBOUNCE_MS * 2));
        act(() => result.current.submit());
        expect(onSearch).not.toHaveBeenCalled();
    });

    it('крестик сбрасывает поиск без ожидания', () => {
        const onSearch = vi.fn();
        const { result } = renderHook(() => useQueueSearch('мира', onSearch));
        act(() => result.current.clear());
        expect(result.current.value).toBe('');
        expect(onSearch).toHaveBeenCalledWith('');
    });
});
