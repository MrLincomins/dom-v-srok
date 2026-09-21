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
