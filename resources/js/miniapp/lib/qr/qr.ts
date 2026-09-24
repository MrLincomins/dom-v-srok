/** К подписанному qr_url подъезды добавляются как есть — в подпись они не входят. */
export function houseQrUrl(qrUrl: string, entrance?: number | null): string {
    if (entrance == null) return qrUrl;
    try {
        const url = new URL(qrUrl, window.location.origin);
        url.searchParams.set('entrance', String(entrance));
        return url.toString();
    } catch {
        return `${qrUrl}${qrUrl.includes('?') ? '&' : '?'}entrance=${entrance}`;
    }
}

export function houseQrFilename(token: string, entrance?: number | null): string {
    return entrance == null ? `qr-${token}.png` : `qr-${token}-${entrance}.png`;
}

export async function downloadQr(url: string, filename: string): Promise<void> {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error('download_failed');
    }
    const blob = await response.blob();
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filename;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);
}
