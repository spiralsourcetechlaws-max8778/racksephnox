import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    // ════════════════════════════════════════════════════════════
    //  PLUGINS
    // ════════════════════════════════════════════════════════════
    plugins: [
        laravel({
            input: [
                // ── Core ──
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/bootstrap.js',

                // ── Theme & Effects ──
                'resources/js/theme.js',
                'resources/js/cosmic-particles.js',
                'resources/js/echo.js',

                // ── Lottery Canvas & Sounds ──
                'resources/js/lottery-canvas.js',
                'resources/js/lottery-sounds.js',
                'resources/js/lottery/canvas-slot.js',
                'resources/js/lottery/sounds.js',
                'resources/js/lottery/svg-symbols.js',
                'resources/js/lottery/symbols.js',
            ],
            refresh: [
                'resources/views/**',
                'routes/**',
                'app/**',
                'config/**',
                'lang/**',
            ],
        }),
    ],

    // ════════════════════════════════════════════════════════════
    //  ALIASES
    // ════════════════════════════════════════════════════════════
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '~': path.resolve(__dirname, 'resources'),
        },
    },

    // ════════════════════════════════════════════════════════════
    //  DEV SERVER — POLLING MODE (ENOSPC-PROOF)
    // ════════════════════════════════════════════════════════════
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: false,
        cors: true,
        origin: 'http://localhost:5173',

        hmr: {
            host: 'localhost',
            protocol: 'ws',
            overlay: true,
        },

        watch: {
            // ─── POLLING MODE — no inotify watchers used ───
            usePolling: true,

            // How often to poll (ms). 1000ms is a good balance:
            //  · fast enough to feel instant when you save a file
            //  · slow enough to keep CPU low on mobile/Proot
            interval: 1000,

            // Ignore heavy directories even in polling mode —
            // polling still scans every path, so fewer paths = less CPU.
            ignored: [
                '**/.git/**',
                '**/.idea/**',
                '**/.vscode/**',
                '**/.fleet/**',
                '**/node_modules/**',
                '**/vendor/**',
                '**/storage/**',
                '**/bootstrap/cache/**',
                '**/public/build/**',
                '**/public/hot',
                '**/database/*.sqlite',
                '**/database/*.sqlite-*',
                '**/*.log',
                '**/backup_migrations/**',
                '**/tests/**',
                '**/.env',
                '**/.env.*',
                '**/*.swp',
                '**/*.swo',
                '**/.DS_Store',
                '**/Thumbs.db',
                '**/coverage/**',
                '**/.phpunit.cache/**',
                '**/node_modules/.vite/**',
            ],

            // Wait for writes to stabilize before triggering rebuild.
            // Critical on slow filesystems (Proot) to avoid double-rebuilds.
            awaitWriteFinish: {
                stabilityThreshold: 500,
                pollInterval: 100,
            },
        },

        fs: {
            strict: false,
            allow: [
                '.',
                '/root/racksephnox',
            ],
        },
    },

    // ════════════════════════════════════════════════════════════
    //  PREVIEW SERVER (production preview)
    // ════════════════════════════════════════════════════════════
    preview: {
        host: '0.0.0.0',
        port: 4173,
        strictPort: false,
        cors: true,
    },

    // ════════════════════════════════════════════════════════════
    //  BUILD CONFIG
    // ════════════════════════════════════════════════════════════
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        sourcemap: false,
        manifest: true,
        target: 'es2020',
        minify: 'esbuild',
        cssMinify: true,
        cssCodeSplit: true,
        chunkSizeWarningLimit: 1500,
        reportCompressedSize: false,

        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios'],
                },
                chunkFileNames: 'assets/[name]-[hash].js',
                entryFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
            },
        },

        commonjsOptions: {
            transformMixedEsModules: true,
        },
    },

    // ════════════════════════════════════════════════════════════
    //  CSS / POSTCSS
    // ════════════════════════════════════════════════════════════
    css: {
        postcss: './postcss.config.js',
        devSourcemap: false,
    },

    // ════════════════════════════════════════════════════════════
    //  DEPENDENCY OPTIMIZATION
    // ════════════════════════════════════════════════════════════
    optimizeDeps: {
        include: [
            'axios',
        ],
        exclude: [],
        esbuildOptions: {
            target: 'es2020',
        },
    },

    // ════════════════════════════════════════════════════════════
    //  LOGGING
    // ════════════════════════════════════════════════════════════
    logLevel: 'info',
    clearScreen: false,
});
