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

/** что диспетчер может сделать дальше, подпись кнопки и статус, порядок = порядок кнопок */
export const NEXT_ACTIONS: Record<RequestStatus, Array<{ status: RequestStatus; label: string }>> = {
    new: [
        { status: 'in_progress', label: 'Взять в работу' },
        { status: 'done', label: 'Выполнено' },
    ],
    assigned: [
        { status: 'in_progress', label: 'В работе' },
        { status: 'done', label: 'Выполнено' },
    ],
    in_progress: [{ status: 'done', label: 'Выполнено' }],
    returned: [
        { status: 'in_progress', label: 'Снова в работу' },
        { status: 'done', label: 'Выполнено' },
    ],
    done: [],
    confirmed: [],
    redirected: [],
};
