import { api } from './client';
import type {
    Contractor,
    ContractorInput,
    Executor,
    ExecutorUpdate,
    House,
    Organization,
    OrganizationUpdate,
} from './types';

export async function getOrganization(signal?: AbortSignal): Promise<Organization> {
    return (await api<{ data: Organization }>('/organization', { signal })).data;
}

export async function updateOrganization(body: OrganizationUpdate): Promise<Organization> {
    return (await api<{ data: Organization }>('/organization', { method: 'PATCH', body })).data;
}

export async function listHouses(signal?: AbortSignal): Promise<House[]> {
    return (await api<{ data: House[] }>('/organization/houses', { signal })).data;
}

export async function updateHouse(
    id: number,
    body: { chat_keywords_enabled?: boolean; entrances?: number },
): Promise<House> {
    return (await api<{ data: House }>(`/organization/houses/${id}`, { method: 'PATCH', body })).data;
}

export async function listExecutors(signal?: AbortSignal): Promise<Executor[]> {
    return (await api<{ data: Executor[] }>('/organization/executors', { signal })).data;
}

export async function createExecutor(body: {
    name: string;
    phone?: string;
    specialty?: string;
}): Promise<Executor> {
    return (await api<{ data: Executor }>('/organization/executors', { method: 'POST', body })).data;
}

export async function updateExecutor(id: number, body: ExecutorUpdate): Promise<Executor> {
    return (await api<{ data: Executor }>(`/organization/executors/${id}`, { method: 'PATCH', body })).data;
}

export async function archiveExecutor(id: number): Promise<void> {
    await api(`/organization/executors/${id}`, { method: 'DELETE' });
}

export async function listContractors(signal?: AbortSignal): Promise<Contractor[]> {
    return (await api<{ data: Contractor[] }>('/organization/contractors', { signal })).data;
}

export async function createContractor(body: ContractorInput): Promise<Contractor> {
    return (await api<{ data: Contractor }>('/organization/contractors', { method: 'POST', body })).data;
}

export async function deleteContractor(id: number): Promise<void> {
    await api(`/organization/contractors/${id}`, { method: 'DELETE' });
}

export async function resetDemo(): Promise<void> {
    await api('/demo/reset', { method: 'POST' });
}
