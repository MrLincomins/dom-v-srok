import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateHouse } from '@/api/organization';
import type { House } from '@/api/types';
import type { UpdateHouseParams } from './HousesCard.types';

export function useHousesCard() {
    const client = useQueryClient();
    const [notice, setNotice] = useState<string | null>(null);
    const save = useMutation({
        mutationFn: ({ id, chat_keywords_enabled, entrances }: UpdateHouseParams) =>
            updateHouse(id, { chat_keywords_enabled, entrances }),
        onMutate: async ({ id, chat_keywords_enabled, entrances }) => {
            await client.cancelQueries({ queryKey: ['organization', 'houses'] });
            const previous = client.getQueryData<House[]>(['organization', 'houses']);
            client.setQueryData(['organization', 'houses'], (current: House[] | undefined) =>
                (current ?? []).map((item) =>
                    item.id === id
                        ? {
                              ...item,
                              ...(chat_keywords_enabled === undefined ? {} : { chat_keywords_enabled }),
                              ...(entrances === undefined ? {} : { entrances }),
                          }
                        : item,
                ),
            );
            return { previous };
        },
        onError: (_error, _next, context) => {
            if (context?.previous) client.setQueryData(['organization', 'houses'], context.previous);
        },
        onSuccess: (house) => {
            client.setQueryData(['organization', 'houses'], (current: House[] | undefined) =>
                (current ?? []).map((item) => (item.id === house.id ? house : item)),
            );
        },
    });

    return { notice, setNotice, save };
}
