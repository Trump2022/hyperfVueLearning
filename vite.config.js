import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';

const env = loadEnv('development', process.cwd(), '');

export default defineConfig({
    plugins: [vue()],
    server: {
        port: 5173,
        proxy: {
            '/api': {
                target: `http://127.0.0.1:${env.HYPERF_PORT || 9501}`,
                changeOrigin: true,
            },
        },
    },
});