const STREET =
    /^(ул\.|улица|пр-кт|пр\.|проспект|пер\.|переулок|б-р|бульвар|ш\.|шоссе|наб\.|пл\.|площадь|д\.|дом)(?:\s|$)/i;
const MARK =
    /(?:^|,\s*)(?:ул\.|улица|пр-кт|пр\.|проспект|пер\.|переулок|б-р|бульвар|ш\.|шоссе|наб\.|набережная|пл\.|площадь|д\.|дом)\s+/gi;

/** Город в начале адреса («Казань, ул. …») убираем, чтобы строка влезала целиком. */
export function withoutCity(address: string): string {
    const trimmed = address.trim();
    const comma = trimmed.indexOf(',');
    if (comma === -1) return trimmed;
    const rest = trimmed.slice(comma + 1).trim();
    return STREET.test(rest) ? rest : trimmed;
}

/** «ул. Демонстрационная, д. 1» → «Демонстрационная, 1». */
export function withoutMarks(address: string): string {
    return withoutCity(address)
        .replace(MARK, (chunk) => (chunk.startsWith(',') ? ', ' : ''))
        .replace(/\s+,/g, ',')
        .replace(/,\s*,/g, ', ')
        .replace(/\s{2,}/g, ' ')
        .trim();
}
