import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    server: {
        host: 'inspectec.test', // o '127.0.0.1' si lo prefieres
        port: 5173,
        cors: true, // ← 👈 ESTA LÍNEA HABILITA LOS HEADERS CORS
        hmr: {
            host: 'inspectec.test', // debe coincidir con tu dominio
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
