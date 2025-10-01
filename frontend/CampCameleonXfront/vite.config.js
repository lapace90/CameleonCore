// vite.config.js
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  // ✅ Charger les variables d'environnement correctement
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [vue()],
    
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: env.VITE_API_URL || 'http://localhost:8000',  // ✅ Utilise env au lieu de import.meta.env
          changeOrigin: true,
          secure: false,
        }
      }
    },
    
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    }
  }
})