import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
export default defineConfig({
  plugins: [laravel({ input: ['resources/js/app.js', 'resources/css/app.css', 'resources/js/login.js'], refresh: true }), vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    hmr: { host: 'localhost', clientPort: 5178 },
  },
});
