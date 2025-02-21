import replace from '@rollup/plugin-replace';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { config } from 'process';
import { defineConfig } from 'vite';
import eslint from 'vite-plugin-eslint';
import liveReload from 'vite-plugin-live-reload';

export default defineConfig({
    plugins: [
        tailwindcss({
            config: './tailwind.config.js',
            cssPath: './src/css/tailwind.css',
            contentPath: './src/css/content.css',
            extract: {
                include: ['**/*.php', '**/*.js'],
                exclude: ['node_modules', 'dist', 'build'],
            },
            safelist: [
                'mc-mobile-menu-open',
                'mc-mobile-menu-close',
                'mc-mobile-menu-toggle',
            ],
            keyframes: true,
            variables: true,
            fontFamily: {
                sans: ['var(--font-sans)'],
                serif: ['var(--font-serif)'],
                mono: ['var(--font-mono)'],
            },
        }),
        vue(),
        liveReload([
            'includes/**/*.php',
            'templates/**/*.php',
            'admin/templates/**/*.php',
        ]),
        replace({
            preventAssignment: true,
            'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV),
            'process.env.MC_API_URL': JSON.stringify(process.env.MC_API_URL || ''),
        }),
        eslint({
            include: ['assets/js/**/*.js'],
        }),
    ],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                admin: resolve(__dirname, 'admin/vue-backend/src/main.js'),
                'admin-style': resolve(__dirname, 'src/css/tailwind.css'),
                'public-style': resolve(__dirname, 'src/css/tailwind.css'),
                'mobile-menu': resolve(
                    __dirname,
                    'modules/MobileMenu/assets/css/mobile-menu.css'
                ),
            },
            output: {
                entryFileNames: `assets/[name].[hash].js`,
                chunkFileNames: `assets/[name].[hash].js`,
                assetFileNames: ({ name }) => {
                    if (/\.css$/.test(name)) {
                        if (name.includes('admin-style')) {
                            return 'admin/css/[name].[hash].css';
                        } else if (name.includes('mobile-menu')) {
                            return 'modules/MobileMenu/assets/css/[name].[hash].css';
                        }
                        return 'public/css/[name].[hash].css';
                    }
                    return 'assets/[name].[hash][extname]';
                },
            },
        },
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'admin/vue-backend/src'),
        },
    },
    optimizeDeps: {
        include: ['vue', 'alpinejs'],
        exclude: ['wordpress-api'],
        esbuildOptions: {
            target: 'es2020',
        },
    },
    server: {
        watch: {
            usePolling: true,
            interval: 1000,
        },
    },
});
