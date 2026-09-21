import { api, downloadFile } from './client';
import type { JournalMeta, JournalQuery, JournalRow } from './types';

export interface JournalPage {
    data: JournalRow[];
    meta: JournalMeta;
}

export function getJournal(query: JournalQuery, signal?: AbortSignal): Promise<JournalPage> {
    return api<JournalPage>('/journal', { query, signal });
}

export function downloadJournalCsv(query: Pick<JournalQuery, 'from' | 'to'>): Promise<void> {
    return downloadFile('/journal.csv', {
        query,
        filename: `zhurnal-zayavok-${query.from ?? ''}-${query.to ?? ''}.csv`,
    });
}
