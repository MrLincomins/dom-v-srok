import { describe, expect, it } from 'vitest';
import { categoryLeaves } from './catalog';
import type { Category } from './types';

describe('категории', () => {
    it('берёт только листья дерева', () => {
        const tree = [
            {
                id: 1,
                slug: 'water',
                name: 'Вода',
                is_emergency: false,
                verify: false,
                children: [
                    { id: 2, slug: 'tap', name: 'Кран', is_emergency: false, verify: false },
                ],
            },
        ] as Category[];

        expect(categoryLeaves(tree).map((item) => item.id)).toEqual([2]);
    });
});
