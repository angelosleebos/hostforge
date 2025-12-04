import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#e0f7fd',
                    100: '#b3ecfa',
                    200: '#80e0f7',
                    300: '#4dd4f4',
                    400: '#26caf1',
                    500: '#26a9e0',
                    600: '#1e8bb8',
                    700: '#176d90',
                    800: '#0f4e68',
                    900: '#083040',
                },
            },
        },
    },
    plugins: [],
};
