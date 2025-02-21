import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import prettier from 'eslint-config-prettier';

export default [
    js.configs.recommended,
    ...vue.configs['flat/recommended'],
    prettier,
    {
        files: ['src/**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 2021,
            sourceType: 'module',
            globals: {
                wp: 'readonly',
                jQuery: 'readonly',
                $: 'readonly'
            }
        },
        plugins: {
            vue
        },
        rules: {
            'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
            'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
            'vue/multi-word-component-names': 'off',
            'vue/require-default-prop': 'off',
            'vue/no-v-html': 'off'
        },
        ignores: [
            'node_modules/**',
            'dist/**',
            '*.d.ts',
            'coverage/**',
            '*.min.js'
        ]
    }
];
