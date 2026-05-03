import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // Windows: ::1:5173 bazen EACCES verir; IPv4 ile dinle.
    // strictPort + hmr.port YOK: port dolunca Vite baska porta gecerken HMR sabit kalirsa
    // sayfa surekli yenilenir (ozellikle giris ekrani).
    server: {
        host: '127.0.0.1',
        port: 24678,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/js/app.js',
                'resources/js/admin.js',
            ],
            // Blade/rota dosyasi her tetiklenince tam sayfa yenileme; Windows/IDE ile
            // gereksiz donguye yol acabiliyor. Blade degisince F5 yeterli.
            refresh: false,
        }),
    ],
});
