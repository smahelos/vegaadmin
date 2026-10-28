import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { copyFileSync, mkdirSync, existsSync } from 'fs';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/invoice-form.js',
                'resources/js/slug-generator.js',
                'resources/js/product-selector.js',
                'resources/js/image-preview.js',
                'resources/js/template-selector.js',
                'resources/js/template-selector-updating.js',
                'resources/js/uels-manager.js',
            ],
            refresh: true,
        }),
    ],
});
