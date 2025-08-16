import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import autoprefixer from 'autoprefixer';
import tailwindcss from 'tailwindcss';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
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
    build: {
        // Optimize chunk splitting
        rollupOptions: {
            external: ['jquery'],
            output: {
                manualChunks: {
                    // Vendor chunks for better caching
                    'vue-vendor': ['vue', '@inertiajs/vue3'],
                    'chart-vendor': ['chart.js', 'vue-chartjs'],
                    'calendar-vendor': ['@fullcalendar/vue3', '@fullcalendar/core', '@fullcalendar/daygrid', '@fullcalendar/interaction', '@fullcalendar/timegrid'],
                    'orgchart-vendor': ['@balkangraph/orgchart.js'],
                    'ui-vendor': ['@heroicons/vue', 'daisyui'],
                    'utils-vendor': ['lodash', 'lodash-es', 'date-fns', 'axios'],
                },
                // Optimize chunk naming
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },
        // Enable minification and tree shaking
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Optimize chunk size warnings
        chunkSizeWarningLimit: 1000,
        // Disable source maps for production
        sourcemap: false,
    },
    // Optimize dependencies
    optimizeDeps: {
        exclude: ['jquery'],
        include: [
            'vue',
            '@inertiajs/vue3',
            'axios',
            'lodash-es',
            'date-fns',
        ],
    },
    // Server optimization
    server: {
        hmr: {
            overlay: false,
        },
    },
    // CSS optimization with proper PostCSS configuration
    css: {
        postcss: {
            plugins: [
                autoprefixer,
                tailwindcss,
            ],
        },
    },

    // --- ADDED THIS BLOCK TO FIX THE TEST ERROR ---
    // This configures the testing environment for Vitest.
    test: {
        // Use 'jsdom' to simulate a browser environment (adds the 'document' object)
        environment: 'jsdom',
        // Make test globals like `describe` and `it` available without imports
        globals: true,
    },
});
