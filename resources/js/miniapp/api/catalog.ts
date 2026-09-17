import { api } from './client';
import type { Category } from './types';

export async function getCategories(): Promise<Category[]> {
    return (await api<{ data: Category[] }>('/catalog/categories')).data;
}
