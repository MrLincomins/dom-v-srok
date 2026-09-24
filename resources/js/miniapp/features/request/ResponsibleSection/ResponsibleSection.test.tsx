import { fireEvent, render, screen } from '@testing-library/react';
import { MaxUI } from '@maxhub/max-ui';
import { describe, expect, it } from 'vitest';
import type { RequestCard } from '@/api/types';
import { ResponsibleSection } from './ResponsibleSection';

const card = {
    status: 'new',
    responsible: { kind: 'organization', name: 'ТСЖ «Демо»', phone: null, is_sure: true },
    deadline_fix_at: '2026-09-27T02:39:00Z',
    deadline_reply_at: '2026-10-08T18:00:00Z',
    basis: 'СанПиН',
    executor: null,
} as RequestCard;

describe('кто и когда сделает', () => {
    it('прячет детали и открывает их по заголовку', () => {
        render(
            <MaxUI platform="android" colorScheme="dark">
                <ResponsibleSection card={card} />
            </MaxUI>,
        );

        const fold = document.querySelector('.section-fold');
        expect(screen.getByRole('button', { name: 'Кто и когда сделает' })).toHaveAttribute(
            'aria-expanded',
            'false',
        );
        expect(fold).not.toHaveClass('is-open');
        expect(fold).toHaveAttribute('aria-hidden', 'true');

        fireEvent.click(screen.getByRole('button', { name: 'Кто и когда сделает' }));

        expect(screen.getByRole('button', { name: 'Кто и когда сделает' })).toHaveAttribute(
            'aria-expanded',
            'true',
        );
        expect(fold).toHaveClass('is-open');
        expect(fold).toHaveAttribute('aria-hidden', 'false');
        expect(screen.getByText('ТСЖ «Демо»')).toBeInTheDocument();
        expect(screen.getByText('СанПиН')).toBeInTheDocument();
    });
});
