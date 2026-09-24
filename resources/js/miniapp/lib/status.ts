import type { RequestStatus } from '@/api/types';

export type StatusTone = 'accepted' | 'progress' | 'ready' | 'done' | 'late' | 'muted';

export const STATUS_LABEL: Record<RequestStatus, string> = {
    new: 'Принято',
    assigned: 'Назначено',
    in_progress: 'В работе',
    done: 'Выполнено',
    confirmed: 'Подтверждено',
    returned: 'Возвращено',
    redirected: 'Переадресовано',
};

/** Коротко и прямо: что сейчас происходит с заявкой. */
export const STATUS_EXPLAIN: Record<RequestStatus, string> = {
    new: 'Заявку приняли',
    assigned: 'Назначили мастера',
    in_progress: 'Сейчас делают',
    done: 'Сделали. Подтвердите, что всё хорошо',
    confirmed: 'Проблема решена',
    returned: 'Вернули мастеру: ещё не готово',
    redirected: 'Передали другой службе',
};

export function statusExplain(status: RequestStatus, overdue: boolean): string {
    const text = STATUS_EXPLAIN[status];
    if (!overdue) return text;
    if (status === 'confirmed' || status === 'redirected') return text;
    return `Срок вышел. ${text}`;
}

export function statusTone(status: RequestStatus, overdue: boolean): StatusTone {
    if (status === 'redirected') return 'muted';
    if (status === 'confirmed') return 'done';
    if (overdue) return 'late';
    if (status === 'done') return 'ready';
    if (status === 'in_progress' || status === 'returned') return 'progress';
    return 'accepted';
}

export function transitionLabel(status: RequestStatus, current: RequestStatus): string {
    if (status === 'in_progress') return current === 'returned' ? 'Снова в работу' : 'Взять в работу';
    if (status === 'done') return 'Отметить выполненной';
    return STATUS_LABEL[status];
}
