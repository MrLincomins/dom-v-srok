// schema.d.ts генерируется из OpenAPI; здесь оставляем только короткие имена для приложения.
import type { components, operations } from './schema';

export type User = components['schemas']['User'];
export type RequestListItem = components['schemas']['RequestListItem'];
export type RequestCard = components['schemas']['RequestCard'];
export type RequestEvent = components['schemas']['RequestEvent'];
export type Attachment = components['schemas']['Attachment'];
export type RequestStatus = components['schemas']['RequestStatus'];
export type AuthToken = components['schemas']['AuthToken'];
export type QueueQuery = NonNullable<operations['listRequests']['parameters']['query']>;
type Counters = components['schemas']['Counters'];

export interface Paginated<T> {
    data: T[];
    meta: { current_page: number; last_page: number; per_page: number; total: number; counters?: Counters };
    links?: unknown;
}
