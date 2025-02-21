/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./**/*.php",
        "./**/*.twig",
        "./assets/js/*.js",
        "./modules/**/*.js",
        "./modules/**/*.css",
        "./admin/vue-backend/src/**/*.vue",
        "./admin/vue-backend/src/**/*.js",
    ],
    theme: {
        extend: {
            colors: {
                primary: '#570df8',
                'primary-focus': '#4506cb',
                secondary: '#f000b8',
                'secondary-focus': '#bd0091',
                accent: '#37cdbe',
                'accent-focus': '#2aa79b',
                neutral: '#3d4451',
                'base-100': '#ffffff',
                info: '#3abff8',
                success: '#36d399',
                warning: '#fbbd23',
                error: '#f87272',
            },
        },
    },
    plugins: [
        require('daisyui'),
        require('@tailwindcss/typography'),
    ],
}
