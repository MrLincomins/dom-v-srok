import { ApiError } from '@/api/client';
import { texts } from '@/app/texts';

export function describeError(error: unknown, failureCount = 1): string {
    if (error instanceof ApiError) {
        if (error.isAuth) return texts.errors.unauthorized;
        if (error.isForbidden) return texts.errors.forbidden;
        if (error.status === 404) return texts.errors.notFound;
        if (error.status === 409) return texts.errors.conflict;
        if (error.status === 422) return texts.errors.validation;
        if (failureCount >= 3) return texts.errors.later;
        if (error.isNetwork) return texts.errors.network;
        if (error.status >= 500) return texts.errors.server;
    }
    if (failureCount >= 3) return texts.errors.later;
    return texts.errors.generic;
}

export function describeLoginError(error: unknown): string {
    if (error instanceof ApiError) {
        if (error.isAuth || error.status === 422) return texts.auth.badCredentials;
        if (error.status === 404) return texts.auth.unavailable;
    }
    return describeError(error);
}
