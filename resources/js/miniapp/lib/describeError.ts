import { ApiError } from '@/api/client';
import { texts } from '@/app/texts';

export function describeError(error: unknown): string {
    if (error instanceof ApiError) {
        if (error.isNetwork) return texts.errors.network;
        if (error.isForbidden) return texts.errors.forbidden;
        if (error.status === 404) return texts.request.notFound;
        return error.message;
    }
    return texts.errors.generic;
}
