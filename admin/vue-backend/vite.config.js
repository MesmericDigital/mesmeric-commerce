import vue from '@vitejs/plugin-vue'
import path from 'path'
import { defineConfig } from 'vite'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src')
        }
    },
    build: {
        outDir: path.resolve(__dirname, 'dist'),
        manifest: true,
        emptyOutDir: true,
        rollupOptions: {
            input: path.resolve(__dirname, 'src/main.js'),
            output: {
                entryFileNames: `js/[name].js`,
                chunkFileNames: `js/[name].js`,
                assetFileNames: (assetInfo) => {
                    if (/\.css$/.test(assetInfo.name)) {
                        return 'css/[name][extname]';
                    }
                    return 'assets/[name][extname]';
                },
            },
        },
    },
    css: {
        postcss: {
            plugins: [
                require('tailwindcss'),
                require('autoprefixer')
            ]
        }
    }
})
