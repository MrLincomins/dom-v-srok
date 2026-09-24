export const VIEWPORT_CONTENT =
    'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover';

/** Запрет зума по жесту и при фокусе в поле — на всех экранах кабинета. */
export function lockViewport(doc = document): void {
    let meta = doc.querySelector<HTMLMetaElement>('meta[name="viewport"]');
    if (!meta) {
        meta = doc.createElement('meta');
        meta.name = 'viewport';
        doc.head.appendChild(meta);
    }
    meta.content = VIEWPORT_CONTENT;
}
