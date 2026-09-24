import { act, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Notice } from '@/components/Notice';
import { CONFIRM_LEAVE_MS } from '@/components/ConfirmDialog/ConfirmDialog.model';

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
                <Notice text="Ссылка скопирована" tone="success" onGone={() => undefined} />
            </div>,
        );
        const notice = screen.getByRole('status');
        expect(notice).toHaveTextContent('Ссылка скопирована');
        expect(notice).toHaveClass('is-success');
        expect(notice.parentElement).toBe(document.body);
        expect(document.getElementById('page')?.contains(notice)).toBe(false);
    });

    it('fades out before calling onGone', () => {
        vi.useFakeTimers();
        const onGone = vi.fn();
        render(<Notice text="Ссылка скопирована" tone="success" onGone={onGone} />);
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

    it('does not hide the notice while the user types', () => {
        vi.useFakeTimers();
        const onGone = vi.fn();
        render(
            <>
                <textarea aria-label="Комментарий" />
                <Notice text="Нет связи" tone="error" onGone={onGone} />
            </>,
        );
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(screen.getByRole('status')).toHaveClass('is-error');
        fireEvent.input(screen.getByLabelText('Комментарий'), { target: { value: 'а' } });
        act(() => {
            vi.advanceTimersByTime(2000);
        });
        expect(screen.getByRole('status')).not.toHaveClass('is-leaving');
        expect(onGone).not.toHaveBeenCalled();
        act(() => {
            vi.advanceTimersByTime(2400);
        });
        expect(screen.getByRole('status')).toHaveClass('is-leaving');
        vi.useRealTimers();
    });

    it('shows a confirm dialog on the document body', () => {
        render(
            <div id="page">
                <ConfirmDialog
                    open
                    title="Убрать этого подрядчика?"
                    confirm="Да, убрать"
                    cancel="Отмена"
                    onConfirm={() => undefined}
                    onCancel={() => undefined}
                />
            </div>,
        );
        const dialog = screen.getByRole('dialog', { name: 'Убрать этого подрядчика?' });
        expect(dialog.parentElement).toHaveClass('confirm-backdrop');
        expect(document.getElementById('page')?.contains(dialog)).toBe(false);
        expect(screen.getByRole('button', { name: 'Да, убрать' })).toHaveClass('btn-confirm');
    });

    it('fades the confirm dialog out before calling onCancel', () => {
        vi.useFakeTimers();
        const onCancel = vi.fn();
        render(
            <ConfirmDialog
                open
                title="Убрать этого подрядчика?"
                confirm="Да, убрать"
                cancel="Отмена"
                onConfirm={() => undefined}
                onCancel={onCancel}
            />,
        );
        fireEvent.click(screen.getByRole('button', { name: 'Отмена' }));
        expect(document.querySelector('.confirm-backdrop')).toHaveClass('is-closing');
        expect(onCancel).not.toHaveBeenCalled();
        act(() => {
            vi.advanceTimersByTime(CONFIRM_LEAVE_MS);
        });
        expect(onCancel).toHaveBeenCalledOnce();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        vi.useRealTimers();
    });

    it('animates the confirm dialog out when it closes after success', () => {
        vi.useFakeTimers();
        const onCancel = vi.fn();
        const { rerender } = render(
            <ConfirmDialog
                open
                title="Убрать этого подрядчика?"
                confirm="Да, убрать"
                cancel="Отмена"
                onConfirm={() => undefined}
                onCancel={onCancel}
            />,
        );
        rerender(
            <ConfirmDialog
                open={false}
                title="Убрать этого подрядчика?"
                confirm="Да, убрать"
                cancel="Отмена"
                onConfirm={() => undefined}
                onCancel={onCancel}
            />,
        );
        expect(document.querySelector('.confirm-backdrop')).toHaveClass('is-closing');
        expect(onCancel).not.toHaveBeenCalled();
        act(() => {
            vi.advanceTimersByTime(CONFIRM_LEAVE_MS);
        });
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        vi.useRealTimers();
    });
});
