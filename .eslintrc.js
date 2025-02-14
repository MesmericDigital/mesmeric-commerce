module.exports = {
    root: true,
    env: {
        node: true,
        browser: true,
        es2021: true,
    },
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
        'prettier'
    ],
    parserOptions: {
        ecmaVersion: 2021,
        sourceType: 'module'
    },
    plugins: ['vue'],
    rules: {
        'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
        'vue/multi-word-component-names': 'off',
        'vue/require-default-prop': 'off',
        'vue/no-v-html': 'off'
    },
    globals: {
        wp: 'readonly',
        jQuery: 'readonly',
        $: 'readonly'
    }
};
