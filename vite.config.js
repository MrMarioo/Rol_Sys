import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        watch: {
            ignored: [
                '!**/node_modules/**',
                '**/vendor/**',
                '**/.docker/**',
            ],
        },
        host: '0.0.0.0',
        hmr: {
            host: process.env.VITE_HMR_HOST || 'rolsys.localhost',
            protocol: 'http',
        },
        cors: true,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
