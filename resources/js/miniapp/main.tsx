import { createRoot } from 'react-dom/client';
import { StrictMode } from 'react';
import { App } from './App';
import { Providers } from './app/providers';
import { getColorScheme, signalReady } from './bridge/maxWebApp';

const container = document.getElementById('app');
if (!container) throw new Error('#app не найден');

document.documentElement.dataset.colorScheme = getColorScheme();

createRoot(container).render(
    <StrictMode>
        <Providers>
            <App />
        </Providers>
    </StrictMode>,
);

signalReady();
