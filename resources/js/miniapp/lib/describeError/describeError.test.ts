import { describe, expect, it } from 'vitest';
import { ApiError } from '@/api/client';
import { describeError, describeLoginError } from './describeError';

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

    it('для входа отличает неверный пароль от истекшей сессии', () => {
        expect(describeLoginError(new ApiError('unauthenticated', 'Неверный логин или пароль', 401))).toBe(
            'Неверный логин или пароль',
        );
        expect(describeError(new ApiError('unauthenticated', 'token expired', 401))).toBe(
            'Сессия истекла. Откройте кабинет заново.',
        );
    });

    it('не отдаёт сырое сообщение сервера', () => {
        expect(describeError(new ApiError('oops', 'undefined is not a function', 418))).toBe(
            'Что-то пошло не так. Попробуйте ещё раз.',
        );
    });
});
