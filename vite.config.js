import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@dashboard': path.resolve(__dirname, 'resources/js/dashboard'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `
                   @use "sass:math";
                @use "bootstrap/scss/functions";
                @use "bootstrap/scss/variables";
                @use "bootstrap/scss/mixins";
                @use "bootstrap/scss/maps";
                @use "bootstrap/scss/utilities";
                @use "bootstrap/scss/mixins/assert";
                `,
                includePaths: [
                    'node_modules',
                    'resources/js/dashboard/styles' // Supaya @use 'style' langsung ke sini
                ],
            },
        },
    },
});
