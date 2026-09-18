import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Providers } from './app/providers';
import { App } from './App';
import { router } from './router';

describe('App', () => {
    it('renders the browser fallback outside MAX', async () => {
        await router.navigate('/app');
        render(
            <Providers>
                <App />
            </Providers>,
        );

        expect(await screen.findByText('Откройте кабинет из бота в MAX')).toBeInTheDocument();
    });
});
