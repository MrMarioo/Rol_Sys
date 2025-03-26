import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default ({ mode }) => {
    process.env = { ...process.env, ...loadEnv(mode, process.cwd()) };
    return defineConfig({
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
                host: process.env.VITE_HMR_HOST,
                clientPort: 5173,
            },
        },
        plugins: [
            laravel({
                input: ['resources/js/app.js'],
                refresh: true,
                publicDirectory: 'public',
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                    compilerOptions: {
                        isCustomElement: (tag) =>
                            ['md-linedivider'].includes(tag),
                    },
                },
            }),
        ],
        build: {
            assetsInlineLimit: 0,
            emptyOutDir: true,
        },
        resolve: {
            alias: {
                '@images': '/resources/images',
            },
        },
    });
};
