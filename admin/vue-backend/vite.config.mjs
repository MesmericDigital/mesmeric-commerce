import replace from '@rollup/plugin-replace'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import { defineConfig } from 'vite'
import eslint from 'vite-plugin-eslint'
import liveReload from 'vite-plugin-live-reload'

export default defineConfig({
    plugins: [
        vue(),
        liveReload([
            'src/**/*.vue',
            'src/**/*.js'
        ]),
        replace({
            preventAssignment: true,
            'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV)
        }),
        {
            ...eslint({
                include: ['src/**/*.js', 'src/**/*.vue'],
                exclude: ['node_modules/**', 'dist/**'],
                cache: false
            }),
            enforce: 'pre'
        }
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'src')
        }
    },
    build: {
        outDir: resolve(__dirname, 'dist'),
        manifest: true,
        emptyOutDir: true,
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'src/main.js'),
                styles: resolve(__dirname, 'src/css/tailwind.css')
            },
            output: {
                entryFileNames: 'js/[name].[hash].js',
                chunkFileNames: 'js/[name].[hash].js',
                assetFileNames: ({ name }) => {
                    if (/\.css$/.test(name)) {
                        return 'css/[name].[hash][extname]'
                    }
                    return 'assets/[name].[hash][extname]'
                }
            }
        }
    },
    optimizeDeps: {
        include: ['vue'],
        exclude: [],
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
})
