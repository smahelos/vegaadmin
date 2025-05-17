import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

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
                'resources/js/product-image-preview.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Sertup sourcemap for easier debugging
        // Vite generates sourcemaps for all files by default
        sourcemap: true,
        // Disable code splitting
        // This is useful for debugging, but can increase the size of the final bundle
        // and may affect performance
        rollupOptions: {
            output: {
                sourcemapPathTransform: (relativeSourcePath, sourcemapPath) => {
                    // Set the source map pathes to be relative to the root directory
                    return relativeSourcePath;
                },
                manualChunks: undefined,
            },
        },
    },
});
