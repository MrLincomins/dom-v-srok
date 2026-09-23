import { afterEach, describe, expect, it, vi } from 'vitest';
import { openExternalLink } from './maxWebApp';

afterEach(() => {
    delete window.WebApp;
    vi.unstubAllGlobals();
});

describe('openExternalLink', () => {
    it('в браузере открывает вкладку, даже если у WebApp есть openLink без сессии', () => {
        const openLink = vi.fn();
        const open = vi.fn(() => ({}) as Window);
        window.WebApp = { openLink };
        vi.stubGlobal('open', open);

        openExternalLink('https://example.test/qr.png?signature=x');

        expect(openLink).not.toHaveBeenCalled();
        expect(open).toHaveBeenCalledWith(
            'https://example.test/qr.png?signature=x',
            '_blank',
            'noopener,noreferrer',
        );
    });

    it('внутри MAX отдаёт ссылку в bridge', () => {
        const openLink = vi.fn();
        const open = vi.fn();
        window.WebApp = { initData: 'query_id=1', openLink };
        vi.stubGlobal('open', open);

        openExternalLink('https://example.test/qr.png');

        expect(openLink).toHaveBeenCalledWith('https://example.test/qr.png');
        expect(open).not.toHaveBeenCalled();
    });

    it('не открывает javascript: ссылки', () => {
        const open = vi.fn();
        vi.stubGlobal('open', open);

        openExternalLink('javascript:alert(1)');

        expect(open).not.toHaveBeenCalled();
    });
});
