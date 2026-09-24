import { describe, expect, it } from 'vitest';
import { measureScrollThumb } from './ScrollArea.model';

describe('measureScrollThumb', () => {
    it('hides the thumb when content fits', () => {
        expect(measureScrollThumb({ scrollTop: 0, scrollHeight: 400, clientHeight: 400 })).toBeNull();
    });

    it('places the thumb at the top and bottom of the track', () => {
        const start = measureScrollThumb({ scrollTop: 0, scrollHeight: 800, clientHeight: 400 });
        const end = measureScrollThumb({ scrollTop: 400, scrollHeight: 800, clientHeight: 400 });
        expect(start).toEqual({ top: 0, height: 200 });
        expect(end).toEqual({ top: 200, height: 200 });
    });
});
