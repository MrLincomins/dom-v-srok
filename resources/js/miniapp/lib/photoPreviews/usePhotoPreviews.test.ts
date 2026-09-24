import { renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { usePhotoPreviews } from './usePhotoPreviews';

afterEach(() => {
    vi.restoreAllMocks();
});

describe('превью фото', () => {
    it('создаёт новый адрес после размонтирования, а не отдаёт отозванный', () => {
        const urls = ['blob:first', 'blob:second'];
        const createObjectURL = vi.spyOn(URL, 'createObjectURL').mockImplementation(() => urls.shift() ?? '');
        const revokeObjectURL = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);
        const files = [new File(['img'], 'leak.jpg', { type: 'image/jpeg' })];

        const first = renderHook(() => usePhotoPreviews(files));
        expect(first.result.current).toEqual([{ name: 'leak.jpg', url: 'blob:first' }]);

        first.unmount();
        expect(revokeObjectURL).toHaveBeenCalledWith('blob:first');

        const second = renderHook(() => usePhotoPreviews(files));
        expect(second.result.current).toEqual([{ name: 'leak.jpg', url: 'blob:second' }]);
        expect(createObjectURL).toHaveBeenCalledTimes(2);
        second.unmount();
    });
});
