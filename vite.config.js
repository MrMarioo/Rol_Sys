import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    define: {
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
    },
    server: {
        watch: {
            ignored: [
                '**/vendor/**',
                '**/.docker/**',
                '**/ai/**',
                '**/storage/**',
            ],
        },
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
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
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true
            }
        }
    }
});
