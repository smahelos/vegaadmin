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
                // přidejte další soubory podle potřeby
            ],
            refresh: true,
        }),
    ],
    build: {
        // Nastavení source map pro debugování
        sourcemap: true,
        // Zachování struktury adresáře pro lepší mapování
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
});
