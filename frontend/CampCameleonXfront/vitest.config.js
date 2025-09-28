import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  // ⚠️ root = dossier frontend pour résoudre node_modules correctement
  root: resolve(__dirname, '.'),

  plugins: [vue()],

  server: {
    // autoriser l'accès deux niveaux au-dessus (racine du repo)
    fs: { allow: ['../..'] },
  },

  // aide Vite à pré-optimiser ces deps si nécessaire
  optimizeDeps: {
    include: ['vue', 'pinia', '@vue/test-utils'],
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },

  test: {
    globals: true,
    environment: 'jsdom',

    // ⚠️ globs POSIX (slashes), *pas* de resolve() pour éviter les backslashes Windows
    include: ['../../tests/**/*.{test,spec}.{js,ts}'],
    exclude: [
      'node_modules/',
      'dist/',
      '../../tests/e2e/**',
      '../../tests/**/*.php',
      '../../vendor/**',
      '../../storage/**',
      '../../bootstrap/**',
    ],
  },
})
