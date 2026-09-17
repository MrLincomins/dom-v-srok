const TIMEZONE = 'Europe/Moscow';

const dateTime = new Intl.DateTimeFormat('ru-RU', {
    timeZone: TIMEZONE,
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
});

const dateOnly = new Intl.DateTimeFormat('ru-RU', { timeZone: TIMEZONE, day: 'numeric', month: 'long' });

export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) return '—';
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '—' : dateTime.format(date);
}

export function formatDate(iso: string | null | undefined): string {
    if (!iso) return '—';
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '—' : dateOnly.format(date);
}
