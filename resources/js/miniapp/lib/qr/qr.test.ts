import { describe, expect, it, vi } from 'vitest';
import { downloadQr, houseQrFilename, houseQrUrl } from './qr';

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

    it('собирает имя файла плаката', () => {
        expect(houseQrFilename('abc')).toBe('qr-abc.png');
        expect(houseQrFilename('abc', 2)).toBe('qr-abc-2.png');
    });

    it('скачивает картинку по ссылке', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => new Response('qr', { status: 200 })),
        );
        const createObjectURL = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:qr');
        const revokeObjectURL = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);
        const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => undefined);

        await downloadQr('https://max.example/qr.png', 'qr-abc.png');
        expect(click).toHaveBeenCalled();
        createObjectURL.mockRestore();
        revokeObjectURL.mockRestore();
        click.mockRestore();
        vi.unstubAllGlobals();
    });
});
