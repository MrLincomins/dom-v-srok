import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { EmptyState } from './EmptyState';
import { ErrorState } from './ErrorState';

describe('screen states', () => {
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
});
