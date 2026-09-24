import type { RequestStatus } from '@/api/types';

export type StatusTone = 'accepted' | 'progress' | 'ready' | 'done' | 'late' | 'muted';
export type StatusAudience = 'staff' | 'resident';
export type StaffPrimaryAction = 'assign' | 'start' | 'finish';

export const STATUS_LABEL: Record<RequestStatus, string> = {
    new: 'Принято',
    assigned: 'Назначено',
    in_progress: 'В работе',
    done: 'Выполнено',
    confirmed: 'Подтверждено',
    returned: 'Возвращено',
    redirected: 'Переадресовано',
};

const STAFF_EXPLAIN: Record<RequestStatus, string> = {
    new: 'Заявку приняли',
    assigned: 'Назначили мастера',
    in_progress: 'Сейчас делают',
    done: 'Ждём, что скажет житель',
    confirmed: 'Проблема решена',
    returned: 'Житель вернул: ещё не готово',
    redirected: 'Передали другой службе',
};

const RESIDENT_EXPLAIN: Record<RequestStatus, string> = {
    new: 'Заявку приняли',
    assigned: 'Назначили мастера',
    in_progress: 'Сейчас делают',
    done: 'Сделали. Подтвердите, что всё хорошо',
    confirmed: 'Проблема решена',
    returned: 'Вернули мастеру: ещё не готово',
    redirected: 'Передали другой службе',
};

export function statusExplain(
    status: RequestStatus,
    overdue: boolean,
    audience: StatusAudience,
): string {
    const text = (audience === 'staff' ? STAFF_EXPLAIN : RESIDENT_EXPLAIN)[status];
    if (!overdue) return text;
    if (status === 'confirmed' || status === 'redirected') return text;
    return `Срок вышел. ${text}`;
}

export function staffPrimaryAction(
    status: RequestStatus,
    hasExecutor = false,
): StaffPrimaryAction | null {
    if (status === 'new') return 'assign';
    if (status === 'returned') return hasExecutor ? 'start' : 'assign';
    if (status === 'assigned') return 'start';
    if (status === 'in_progress') return 'finish';
    return null;
}

export function statusTone(status: RequestStatus, overdue: boolean): StatusTone {
    if (status === 'redirected') return 'muted';
    if (status === 'confirmed') return 'done';
    if (status === 'done') return 'ready';
    if (status === 'in_progress' || status === 'returned') return 'progress';
    if (overdue) return 'late';
    return 'accepted';
}

