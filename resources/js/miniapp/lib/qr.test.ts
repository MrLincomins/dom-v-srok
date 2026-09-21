import { describe, expect, it } from 'vitest';
import { houseQrUrl } from './qr';

describe('ссылка на QR дома', () => {
    it('не трогает url без подъезда', () => {
        expect(houseQrUrl('https://max.example/qr.png?signature=x')).toBe(
            'https://max.example/qr.png?signature=x',
        );
    });

    it('добавляет подъезд к подписанной ссылке', () => {
        expect(houseQrUrl('https://max.example/qr.png?signature=x', 2)).toBe(
            'https://max.example/qr.png?signature=x&entrance=2',
        );
    });
});
