import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { StatusChip } from '@/components/StatusChip';

describe('StatusChip', () => {
    it('renders russian label', () => {
        render(<StatusChip status="in_progress" />);
        expect(screen.getByText('В работе')).toBeInTheDocument();
    });

    it('uses late tone when overdue', () => {
        render(<StatusChip status="new" overdue />);
        expect(screen.getByText('Принято').className).toContain('text-late');
    });
});
