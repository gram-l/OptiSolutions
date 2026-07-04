import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        cors: true,
        hmr: {
            host: '192.168.254.147', // your PC's IP
        },
    },

    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/login.css',

                'resources/css/admin_css/dashboard.css',

                'resources/css/patient_css/about.css',
                'resources/css/patient_css/app.css',
                'resources/css/patient_css/chatbot.css',
                'resources/css/patient_css/contact.css',
                'resources/css/patient_css/doctors.css',
                'resources/css/patient_css/home.css',
                'resources/css/patient_css/services.css',

                'resources/js/app.js',
                'resources/js/bootstrap.js',
                'resources/js/navbar-loader.js',
                'resources/js/script.js',
            ],
            refresh: true,
        }),
    ],
});

//npm run dev --host