import type { JournalSummary } from '@/api/types';

export type JournalStatKey = Exclude<keyof JournalSummary, 'first_reaction_minutes'>;

export interface JournalStat {
    key: JournalStatKey;
    label: string;
    alert?: boolean;
}
