import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {

            /* =========================
               COLORS – Baliuag University
            ========================== */
            colors: {
                bu: {
                    DEFAULT: '#1B5E20',
                    dark: '#145A1F',
                    light: '#E8F5E9',
                    muted: '#F3F7F4',
                },

                status: {
                    success: '#2E7D32',
                    warning: '#F9A825',
                    danger: '#C62828',
                    info: '#1565C0',
                },
            },

            /* =========================
               TYPOGRAPHY
            ========================== */
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            /* =========================
               SHADOWS
            ========================== */
            boxShadow: {
                card: '0 6px 18px rgba(0,0,0,0.06)',
                soft: '0 2px 8px rgba(0,0,0,0.05)',
            },

            /* =========================
               BORDER RADIUS
            ========================== */
            borderRadius: {
                xl: '0.9rem',
                '2xl': '1.25rem',
            },
        },
    },

    plugins: [forms], // ✅ THIS WAS MISSING
};
