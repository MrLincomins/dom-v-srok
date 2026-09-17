// типы из docs/openapi.yaml (npm run api:types), руками правится только этот файл
import type { components, operations } from './schema';

export type User = components['schemas']['User'];
export type Organization = components['schemas']['Organization'];
export type House = components['schemas']['House'];
export type Category = components['schemas']['Category'];
export type RequestListItem = components['schemas']['RequestListItem'];
export type RequestCard = components['schemas']['RequestCard'];
export type RequestEvent = components['schemas']['RequestEvent'];
export type Attachment = components['schemas']['Attachment'];
export type Executor = components['schemas']['Executor'];
export type RequestStatus = components['schemas']['RequestStatus'];
export type Counters = components['schemas']['Counters'];
export type AuthToken = components['schemas']['AuthToken'];
export type QueueQuery = NonNullable<operations['listRequests']['parameters']['query']>;

export interface Paginated<T> {
    data: T[];
    meta: { current_page: number; last_page: number; per_page: number; total: number; counters?: Counters };
    links?: unknown;
}
