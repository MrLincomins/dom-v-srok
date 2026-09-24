const TIMEZONE = 'Europe/Moscow';
const MS_DAY = 86_400_000;

const dateTime = new Intl.DateTimeFormat('ru-RU', {
    timeZone: TIMEZONE,
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
});

const dateInput = new Intl.DateTimeFormat('en-CA', {
    timeZone: TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
});

export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) return '—';
    const date = new Date(iso);
    return Number.isNaN(date.getTime()) ? '—' : dateTime.format(date);
}

export function formatDateInput(date: Date = new Date()): string {
    return dateInput.format(date);
}

export function startOfMonthInput(date: Date = new Date()): string {
    return `${formatDateInput(date).slice(0, 8)}01`;
}

export function daysBetween(from: string, to: string): number | null {
    const start = Date.parse(`${from}T00:00:00`);
    const end = Date.parse(`${to}T00:00:00`);
    if (Number.isNaN(start) || Number.isNaN(end)) return null;
    return Math.round((end - start) / MS_DAY);
}
