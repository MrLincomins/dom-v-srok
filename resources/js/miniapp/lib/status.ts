import type { RequestStatus } from '@/api/types';

export type StatusTone = 'fresh' | 'work' | 'done' | 'late' | 'muted';

export const STATUS_LABEL: Record<RequestStatus, string> = {
    new: 'Принято',
    assigned: 'Назначено',
    in_progress: 'В работе',
    done: 'Выполнено, ждёт подтверждения',
    confirmed: 'Подтверждено',
    returned: 'Возвращено',
    redirected: 'Переадресовано',
};

export function statusTone(status: RequestStatus, overdue: boolean): StatusTone {
    if (overdue) return 'late';
    switch (status) {
        case 'new':
            return 'fresh';
        case 'assigned':
        case 'in_progress':
        case 'done':
            return 'work';
        case 'returned':
            return 'late';
        case 'confirmed':
            return 'done';
        case 'redirected':
            return 'muted';
    }
}

export function transitionLabel(status: RequestStatus, current: RequestStatus): string {
    if (status === 'in_progress') return current === 'returned' ? 'Снова в работу' : 'Взять в работу';
    if (status === 'done') return 'Отметить выполненной';
    return STATUS_LABEL[status];
}
