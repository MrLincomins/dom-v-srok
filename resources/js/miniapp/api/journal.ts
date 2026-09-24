import { openExternalLink } from '@/bridge/maxWebApp';
import { ApiError, api } from './client';
import type { JournalMeta, JournalQuery, JournalRow } from './types';

export interface JournalPage {
    data: JournalRow[];
    meta: JournalMeta;
}

export function getJournal(query: JournalQuery, signal?: AbortSignal): Promise<JournalPage> {
    return api<JournalPage>('/journal', { query, signal });
}

export async function downloadJournalCsv(
    query: Pick<JournalQuery, 'from' | 'to'>,
    signal?: AbortSignal,
): Promise<void> {
    const response = await api<{ data: { url: string } }>('/journal/csv-link', { query, signal });
    const url = response.data?.url;
    if (typeof url !== 'string' || url === '') {
        throw new ApiError('invalid_response', 'Сервер вернул некорректный ответ', 200);
    }
    openExternalLink(url);
}
