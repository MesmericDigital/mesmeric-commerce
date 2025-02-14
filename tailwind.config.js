/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './admin/**/*.{php,vue,js}',
    './public/**/*.php',
    './modules/**/*.php',
    './woocommerce/templates/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
          950: '#082f49',
        },
        secondary: {
          50: '#fdf4ff',
          100: '#fae8ff',
          200: '#f5d0fe',
          300: '#f0abfc',
          400: '#e879f9',
          500: '#d946ef',
          600: '#c026d3',
          700: '#a21caf',
          800: '#86198f',
          900: '#701a75',
          950: '#4a044e',
        },
      },
      fontFamily: {
        sans: ['Inter var', 'system-ui', 'sans-serif'],
      },
      spacing: {
        '128': '32rem',
        '144': '36rem',
      },
      borderRadius: {
        '4xl': '2rem',
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
    require('daisyui'),
  ],
  daisyui: {
    themes: [
      {
        light: {
          ...require('daisyui/src/theming/themes')['light'],
          primary: '#0ea5e9',
          'primary-focus': '#0284c7',
          'primary-content': '#ffffff',
          secondary: '#d946ef',
          'secondary-focus': '#c026d3',
          'secondary-content': '#ffffff',
        },
        dark: {
          ...require('daisyui/src/theming/themes')['dark'],
          primary: '#0ea5e9',
          'primary-focus': '#0284c7',
          'primary-content': '#ffffff',
          secondary: '#d946ef',
          'secondary-focus': '#c026d3',
          'secondary-content': '#ffffff',
        },
      },
    ],
    darkTheme: 'dark',
    base: true,
    styled: true,
    utils: true,
    prefix: 'daisy-',
    logs: false,
    themeRoot: ':root',
  },
}
