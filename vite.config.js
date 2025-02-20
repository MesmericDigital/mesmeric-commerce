import { defineConfig } from 'vite';
import { resolve } from 'path';
import vue from '@vitejs/plugin-vue';
import liveReload from 'vite-plugin-live-reload';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import replace from '@rollup/plugin-replace';
import eslint from 'vite-plugin-eslint';
import { terser } from 'vite-plugin-terser';

export default defineConfig({
    build: {
        manifest: true,
        minify: 'terser',
        sourcemap: process.env.NODE_ENV === 'development',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info'],
                passes: 3
            }
        },
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'assets/js/mc-faq.js'),
                admin: resolve(__dirname, 'admin/js/mesmeric-commerce-admin.js'),
            },
            output: {
                manualChunks: {
                    vendor: ['vue', 'alpinejs'],
                    utils: ['lodash-es', 'axios']
                },
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: 'assets/[ext]/[name]-[hash].[ext]'
            }
        },
        outDir: resolve(__dirname, 'public'),
        assetsInlineLimit: 4096,
        cssCodeSplit: true,
        chunkSizeWarningLimit: 500,
        emptyOutDir: true
    },
    plugins: [
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
        viteStaticCopy({
            targets: [
                {
                    src: 'assets/images/*',
                    dest: 'images'
                }
            ]
        }),
        replace({
            preventAssignment: true,
            'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV),
            'process.env.MC_API_URL': JSON.stringify(process.env.MC_API_URL || '')
        }),
        eslint({
            include: ['assets/js/**/*.js', 'assets/js/**/*.vue']
        }),
        terser({
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_getters: true,
                unsafe: true,
                unsafe_comps: true
            },
            mangle: {
                keep_classnames: true,
                keep_fnames: true,
                properties: false,
                toplevel: true
            },
            format: {
                comments: false
            }
        })
    ],
    optimizeDeps: {
        include: ['vue', 'alpinejs', 'htmx'],
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
