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
                'resources/js/bank-fields.js',
                'resources/js/ares-lookup.js',
                'resources/js/invoice-form.js',
                'resources/js/supplier-form-data.js',
                'resources/js/client-form-data.js',
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
    build: {
        // Setup sourcemap for easier debugging
        // Vite generates sourcemaps for all files by default
        sourcemap: true,
        // Disable code splitting
        // This is useful for debugging, but can increase the size of the final bundle
        // and may affect performance
        rollupOptions: {
            output: {
                sourcemapPathTransform: (relativeSourcePath, sourcemapPath) => {
                    // Set the source map paths to be relative to the root directory
                    return relativeSourcePath;
                },
                manualChunks: undefined,
            },
        },
    },
});
