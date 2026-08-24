import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
    // Explicitly tell Vite about PostCSS
    css: {
        postcss: './postcss.config.js',
    },
    build: {
        // Ensure CSS is extracted properly
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                // Better chunking for CSS
                manualChunks: undefined,
            },
        },
    },
});