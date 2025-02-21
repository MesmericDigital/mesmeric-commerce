/** @type {import('tailwindcss').Config} */
import daisyui from 'daisyui'
import typography from '@tailwindcss/typography'

export default {
    content: [
        './src/**/*.{vue,js,ts,jsx,tsx}',
        './index.html',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    focus: 'var(--color-primary-focus)',
                },
                secondary: {
                    DEFAULT: 'var(--color-secondary)',
                    focus: 'var(--color-secondary-focus)',
                },
                accent: {
                    DEFAULT: 'var(--color-accent)',
                    focus: 'var(--color-accent-focus)',
                },
                neutral: 'var(--color-neutral)',
                'base-100': 'var(--color-base-100)',
                info: 'var(--color-info)',
                success: 'var(--color-success)',
                warning: 'var(--color-warning)',
                error: 'var(--color-error)',
            },
        },
    },
    plugins: [
        daisyui,
        typography,
    ],
    daisyui: {
        themes: ['light', 'dark'],
        styled: true,
        base: true,
        utils: true,
        logs: true,
        rtl: false,
        prefix: '',
        darkTheme: 'dark',
    },
};
