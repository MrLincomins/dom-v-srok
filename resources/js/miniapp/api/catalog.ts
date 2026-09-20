import { api } from './client';
import type { Category } from './types';

export async function listCategories(signal?: AbortSignal): Promise<Category[]> {
    return (await api<{ data: Category[] }>('/catalog/categories', { signal })).data;
}

export function categoryLeaves(nodes: Category[]): Category[] {
    return nodes.flatMap((node) => (node.children?.length ? categoryLeaves(node.children) : [node]));
}
