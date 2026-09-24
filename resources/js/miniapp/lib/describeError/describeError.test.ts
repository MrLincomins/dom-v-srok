import { describe, expect, it } from 'vitest';
import { ApiError } from '@/api/client';
import { describeError } from './describeError';

describe('describeError', () => {
    it('разбирает 403 и 404 без текста сервера', () => {
        expect(describeError(new ApiError('forbidden', 'Access denied lol', 403))).toBe(
            'Нет доступа к этому действию.',
        );
        expect(describeError(new ApiError('not_found', 'Request missing xyz', 404))).toBe(
            'Такой заявки нет.',
        );
    });

    it('на третьей ошибке просит повторить позже', () => {
        expect(describeError(new ApiError('network', 'socket hang up', 0), 3)).toBe(
            'Извините, повторите позже.',
        );
        expect(describeError(new ApiError('server', 'SQLSTATE boom', 500), 3)).toBe(
            'Извините, повторите позже.',
        );
    });

    it('не отдаёт сырое сообщение сервера', () => {
        expect(describeError(new ApiError('oops', 'undefined is not a function', 418))).toBe(
            'Что-то пошло не так. Попробуйте ещё раз.',
        );
    });
});
