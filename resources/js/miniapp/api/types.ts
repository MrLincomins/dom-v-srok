// Короткие имена для приложения. Полная схема — в schema.d.ts, её собирает OpenAPI.
import type { components, operations } from './schema';

export type User = components['schemas']['User'];
export type RequestListItem = components['schemas']['RequestListItem'];
export type RequestCard = components['schemas']['RequestCard'];
export type RequestEvent = components['schemas']['RequestEvent'];
export type Attachment = components['schemas']['Attachment'];
export type RequestStatus = components['schemas']['RequestStatus'];
export type AuthToken = components['schemas']['AuthToken'];
export type Organization = components['schemas']['Organization'];
export type OrganizationUpdate = components['schemas']['OrganizationUpdate'];
export type House = components['schemas']['House'];
export type Category = components['schemas']['Category'];
export type Executor = components['schemas']['Executor'];
export type ExecutorUpdate = components['schemas']['ExecutorUpdate'];
export type Contractor = components['schemas']['Contractor'];
export type ContractorInput = components['schemas']['ContractorInput'];
export type JournalRow = components['schemas']['JournalRow'];
export type JournalSummary = components['schemas']['JournalSummary'];
export type JournalMeta = components['schemas']['JournalMeta'];
export type JournalCsvLink = components['schemas']['JournalCsvLink'];
export type QueueQuery = NonNullable<operations['listRequests']['parameters']['query']>;
export type JournalQuery = NonNullable<operations['journal']['parameters']['query']>;
type Counters = components['schemas']['Counters'];

export interface Paginated<T> {
    data: T[];
    meta: { current_page: number; last_page: number; per_page: number; total: number; counters?: Counters };
    links?: unknown;
}
