import { act, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Notice } from '@/components/Notice';

describe('screen states', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders an empty-state explanation', () => {
        render(<EmptyState text="Заявок нет" />);
        expect(screen.getByText('Заявок нет')).toBeInTheDocument();
    });

    it('lets the user retry after an error', () => {
        const retry = vi.fn();
        render(<ErrorState message="Нет связи" onRetry={retry} />);
        fireEvent.click(screen.getByRole('button', { name: 'Повторить' }));
        expect(retry).toHaveBeenCalledOnce();
    });

    it('shows a notice on the document body', () => {
        render(
            <div id="page">
                <Notice text="Ссылка скопирована" onGone={() => undefined} />
            </div>,
        );
        const notice = screen.getByRole('status');
        expect(notice).toHaveTextContent('Ссылка скопирована');
        expect(notice.parentElement).toBe(document.body);
        expect(document.getElementById('page')?.contains(notice)).toBe(false);
    });

    it('fades out before calling onGone', () => {
        vi.useFakeTimers();
        const onGone = vi.fn();
        render(<Notice text="Ссылка скопирована" onGone={onGone} />);
        expect(screen.getByRole('status')).not.toHaveClass('is-leaving');
        act(() => {
            vi.advanceTimersByTime(2400);
        });
        expect(screen.getByRole('status')).toHaveClass('is-leaving');
        expect(onGone).not.toHaveBeenCalled();
        act(() => {
            vi.advanceTimersByTime(280);
        });
        expect(onGone).toHaveBeenCalledOnce();
        vi.useRealTimers();
    });
});
