import replace from '@rollup/plugin-replace';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { defineConfig } from 'vite';
import eslint from 'vite-plugin-eslint';
import liveReload from 'vite-plugin-live-reload';
import tailwindcss from 'vite-plugin-tailwindcss';

export default defineConfig({
    plugins: [
        tailwindcss(),
        vue({
            template: {
                compilerOptions: {
                    isCustomElement: tag => tag.includes('wp-') || tag.includes('mc-')
                }
            }
        }),
        liveReload([
            'includes/**/*.php',
            'templates/**/*.php',
            'admin/templates/**/*.php'
        ]),
        replace({
            preventAssignment: true,
            'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV),
            'process.env.MC_API_URL': JSON.stringify(process.env.MC_API_URL || '')
        }),
        eslint({
            include: ['assets/js/**/*.js', 'assets/js/**/*.vue']
        })
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
            },
            output: {
                entryFileNames: `assets/[name].[hash].js`,
                chunkFileNames: `assets/[name].[hash].js`,
                assetFileNames: ({ name }) => {
                    if (/\.css$/.test(name)) {
                        return name.includes('admin-style')
                            ? 'admin/css/[name].[hash].css'
                            : 'public/css/[name].[hash].css'
                    }
                    return 'assets/[name].[hash][extname]'
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
            target: 'es2020'
        }
    },
    server: {
        watch: {
            usePolling: true,
            interval: 1000
        }
    }
});
