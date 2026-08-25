import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/login.css',
                'resources/css/staff.css',

                'resources/css/admin_css/dashboard.css',
                'resources/css/admin_css/appointments.css',
                
                'resources/css/admin_css/profile.css',
                'resources/css/admin_css/settings.css',
                'resources/css/admin_css/chatbot_logs.css',
                'resources/css/admin_css/doctors.css',
                'resources/css/admin_css/patients.css',
                'resources/css/admin_css/feedback.css',
                'resources/css/admin_css/header.css',
                'resources/css/admin_css/sidebar.css',
                'resources/css/admin_css/user_management.css',
                'resources/css/admin_css/system_settings.css',

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
                'resources/js/privacy-notice.js',
                'resources/js/script.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '192.168.254.147',
            // host: '127.0.0.1',
           // host: '192.168.1.11',
            //host: '192.168.254.147',
           // host: '10.244.36.34', 

            // lipa bsu 192.168.193.172
        },
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});