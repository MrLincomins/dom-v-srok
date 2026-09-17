import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';

// миниапп собирается внутри laravel в public/build, тот же домен что и апи
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/miniapp/main.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': path.resolve(import.meta.dirname, 'resources/js/miniapp') },
    },
    server: {
        watch: { ignored: ['**/storage/framework/views/**', '**/vendor/**'] },
    },
    build: {
        sourcemap: false,
        chunkSizeWarningLimit: 600,
    },
});
