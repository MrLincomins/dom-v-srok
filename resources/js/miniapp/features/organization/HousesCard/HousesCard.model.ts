import { useEffect, useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { updateHouse } from '@/api/organization';
import type { House } from '@/api/types';
import { texts } from '@/app/texts';
import { useNoticeState } from '@/components/Notice';
import { copyToClipboard } from '@/lib/clipboard';
import type { UpdateHouseParams } from './HousesCard.types';

const COPIED_MS = 2000;

export function useHousesCard() {
    const client = useQueryClient();
    const notice = useNoticeState();
    const [copiedKey, setCopiedKey] = useState<string | null>(null);
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

    useEffect(() => {
        if (!copiedKey) return undefined;
        const done = window.setTimeout(() => setCopiedKey(null), COPIED_MS);
        return () => window.clearTimeout(done);
    }, [copiedKey]);

    const copy = (key: string, value: string) => {
        void copyToClipboard(value).then((ok) => {
            if (!ok) return;
            setCopiedKey(key);
            notice.show(texts.app.copied, 'success');
        });
    };

    return { notice, copiedKey, copy, save };
}
