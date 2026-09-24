import { api } from './client';
import type { JournalCsvLink, JournalMeta, JournalQuery, JournalRow } from './types';

export interface JournalPage {
    data: JournalRow[];
    meta: JournalMeta;
}

export function getJournal(query: JournalQuery, signal?: AbortSignal): Promise<JournalPage> {
    return api<JournalPage>('/journal', { query, signal });
}

export async function getJournalCsvLink(query: Pick<JournalQuery, 'from' | 'to'>): Promise<JournalCsvLink> {
    return (await api<{ data: JournalCsvLink }>('/journal/csv-link', { query })).data;
}
