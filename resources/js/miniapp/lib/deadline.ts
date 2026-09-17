const HOUR = 3_600_000;
const DAY = 24 * HOUR;

interface DeadlineView {
    text: string;
    overdue: boolean;
    urgent: boolean;
}

export function describeDeadline(
    deadlineIso: string | null | undefined,
    now: Date = new Date(),
): DeadlineView {
    if (!deadlineIso) return { text: 'срок по договору', overdue: false, urgent: false };

    const deadline = new Date(deadlineIso).getTime();
    if (Number.isNaN(deadline)) return { text: 'срок не задан', overdue: false, urgent: false };

    const diff = deadline - now.getTime();
    if (diff < 0) return { text: `просрочено на ${humanSpan(-diff)}`, overdue: true, urgent: false };
    return { text: `осталось ${humanSpan(diff)}`, overdue: false, urgent: diff < 4 * HOUR };
}

function humanSpan(ms: number): string {
    if (ms < HOUR) {
        const minutes = Math.max(1, Math.round(ms / 60_000));
        return `${minutes} ${plural(minutes, 'минуту', 'минуты', 'минут')}`;
    }
    if (ms < DAY) {
        const hours = Math.round(ms / HOUR);
        return `${hours} ч`;
    }
    const days = Math.round(ms / DAY);
    return `${days} ${plural(days, 'день', 'дня', 'дней')}`;
}

export function plural(n: number, one: string, few: string, many: string): string {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return one;
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return few;
    return many;
}
