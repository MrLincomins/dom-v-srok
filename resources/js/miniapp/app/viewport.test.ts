import { describe, expect, it } from 'vitest';
import { lockViewport, VIEWPORT_CONTENT } from './viewport';

describe('viewport', () => {
    it('ставит запрет масштаба на весь документ', () => {
        lockViewport();
        expect(document.querySelector<HTMLMetaElement>('meta[name="viewport"]')?.content).toBe(
            VIEWPORT_CONTENT,
        );
    });
});
