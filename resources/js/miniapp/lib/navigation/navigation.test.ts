import { describe, expect, it } from 'vitest';
import { edgeSwipeAllowed } from './navigation';

describe('свайп назад', () => {
    it('выключен на ПК', () => {
        expect(edgeSwipeAllowed('desktop', false)).toBe(false);
        expect(edgeSwipeAllowed('android', true)).toBe(false);
        expect(edgeSwipeAllowed('web', true)).toBe(false);
    });

    it('включён на телефоне', () => {
        expect(edgeSwipeAllowed('android', false)).toBe(true);
        expect(edgeSwipeAllowed('ios', false)).toBe(true);
    });
});
