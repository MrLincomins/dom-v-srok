import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { RouteError } from './RouteError';

describe('RouteError', () => {
    it('показывает русскую заглушку вместо страницы React Router', () => {
        render(<RouteError />);
        expect(screen.getByText('Страница сломалась. Обновите или вернитесь назад.')).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Повторить' })).toBeInTheDocument();
    });
});
