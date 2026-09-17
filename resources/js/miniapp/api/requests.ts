import { api } from './client';
import type { Paginated, QueueQuery, RequestCard, RequestListItem, RequestStatus } from './types';

export function getQueue(query: QueueQuery, signal?: AbortSignal): Promise<Paginated<RequestListItem>> {
    return api<Paginated<RequestListItem>>('/requests', { query, signal });
}

export async function getRequest(id: number, signal?: AbortSignal): Promise<RequestCard> {
    return (await api<{ data: RequestCard }>(`/requests/${id}`, { signal })).data;
}

export async function changeStatus(
    id: number,
    status: RequestStatus,
    comment?: string,
): Promise<RequestCard> {
    return (
        await api<{ data: RequestCard }>(`/requests/${id}/status`, {
            method: 'PATCH',
            body: { status, comment },
        })
    ).data;
}

export async function assignExecutor(id: number, executorId: number, comment?: string): Promise<RequestCard> {
    return (
        await api<{ data: RequestCard }>(`/requests/${id}/assign`, {
            body: { executor_id: executorId, comment },
        })
    ).data;
}

export async function closeRequest(id: number, comment: string, photos: File[]): Promise<RequestCard> {
    const formData = new FormData();
    formData.set('comment', comment);
    photos.forEach((photo) => formData.append('photos[]', photo));
    return (await api<{ data: RequestCard }>(`/requests/${id}/close`, { formData })).data;
}

export async function redirectRequest(
    id: number,
    data: { responsible_party_id?: number; name?: string; phone?: string; note?: string },
): Promise<RequestCard> {
    return (await api<{ data: RequestCard }>(`/requests/${id}/redirect`, { body: data })).data;
}

export async function addComment(id: number, text: string): Promise<RequestCard> {
    return (await api<{ data: RequestCard }>(`/requests/${id}/comments`, { body: { text } })).data;
}

export async function confirmRequest(id: number, resolved: boolean, comment?: string): Promise<RequestCard> {
    return (await api<{ data: RequestCard }>(`/requests/${id}/confirm`, { body: { resolved, comment } }))
        .data;
}

export function myRequests(signal?: AbortSignal): Promise<Paginated<RequestListItem>> {
    return api<Paginated<RequestListItem>>('/my/requests', { signal });
}
