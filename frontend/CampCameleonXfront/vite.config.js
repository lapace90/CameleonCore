import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,  // Port du serveur de dev (changez si besoin)
    proxy: {
      '/api': {
        target: 'http://localhost:8000',  // URL du backend Laravel
        changeOrigin: true,               // pour que l'Origin header corresponde à la cible
        secure: false,                    // désactive la vérif SSL si HTTPS (inutile en HTTP)
        // rewrite: (path) => path.replace(/^\/api/, ''), // PAS nécessaire: on garde /api
      }
    }
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))  // alias @ vers ./src
    }
  }
});
