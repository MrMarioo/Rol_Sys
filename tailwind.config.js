const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    mode: 'jit',
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './node_modules/tw-elements/dist/js/**/*.js',
        './node_modules/@netblink/**/*.js',
    ],

    theme: {
        container: {
            center: true,
        },
        extend: {
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dceafd',
                    200: '#c1dafc',
                    300: '#96c4fa',
                    400: '#569bf5',
                    500: '#4081f1',
                    600: '#2a62e6',
                    700: '#224ed3',
                    800: '#2240ab',
                    900: '#213a87',
                    950: '#192552',
                    DEFAULT: '#4081f1',
                    section: '#4081f1',
                    gradient: '#4081f1',
                },

                accent: {
                    50: '#edefff',
                    100: '#dee2ff',
                    200: '#c3c8ff',
                    300: '#9ea3ff',
                    400: '#7d78ff',
                    500: '#7867fc',
                    600: '#5d3af1',
                    700: '#512dd5',
                    800: '#4127ac',
                    900: '#382887',
                    950: '#23174f',
                    DEFAULT: '#7867fc',
                    section: '#6c59f7',
                    gradient: '#fd6cad',
                },
                white: '#ffffff',
                black: '#1d1d1b',
                blue: {
                    dark: '#070C42',
                },
                gray: {
                    light: '#F9F9F9',
                },
            },
            boxShadow: {
                box: '0px 0px 1.2px rgba(0, 0, 0, 0.029),  0px 0px 2.9px rgba(0, 0, 0, 0.039),  0px 0px 5.4px rgba(0, 0, 0, 0.045), 0px 0px 9.6px rgba(0, 0, 0, 0.051), 0px 0px 18px rgba(0, 0, 0, 0.059), 0px 0px 43px rgba(0, 0, 0, 0.08)',
            },
        },
        fontSize: {
            xxs: '.65rem',
            xs: '.75rem',
            sm: '.875rem',
            tiny: '.875rem',
            base: '1rem',
            lg: '1.125rem',
            xl: '1.25rem',
            '2xl': '1.5rem',
            '3xl': '1.875rem',
            '4xl': '2.25rem',
            '5xl': '3rem',
            '6xl': '4rem',
            '7xl': '5rem',
        },
        screens: {
            xxs: '410px',
            xs: '490px',
            sm: '640px',
            md: '768px',
            lg: '1024px',
            xl: '1084px',
            laptop: '1084px',
            '2xl': '1084px',
            '3xl': '1320px',
            '4xl': '1560px',
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('tw-elements/dist/plugin'),
    ],
};
