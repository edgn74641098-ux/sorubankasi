import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Bootstrap navbar uses `.collapse`; Tailwind's same-named utility sets
    // `visibility: collapse` and hides the menu while keeping layout — disable it.
    corePlugins: {
        collapse: false,
    },

    theme: {
        extend: {
            fontFamily: {
                sans: defaultTheme.fontFamily.sans,
            },
        },
    },

    plugins: [forms],
};
