import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5179,
        strictPort: true,
        //origin: 'http://192.168.1.69:5179', Casa Dona Cida
        //origin: 'http://192.168.18.6:5179', //Casa Mãe
        origin: 'http://192.168.1.3:5179', //Casa Ferraz
        
        hmr: {
            //host: '192.168.1.69',//Casa Dona Cida
            //host: '192.168.18.6', //Casa Mãe
            host: '192.168.1.3', //Casa Ferraz
            port: 5179,
        },
        cors: {
            origin: ['http://localhost:8089', 'http://127.0.0.1:8089', 'http://192.168.1.69:8089', 'http://192.168.18.6:8089', 'http://192.168.1.3:8089'],
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
