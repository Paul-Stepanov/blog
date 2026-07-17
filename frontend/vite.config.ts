import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    // Fallback для прямого доступа к Vite dev-серверу (http://localhost:5173).
    // Канон dev — через nginx (:80), там same-origin и прокси не нужен.
    // Здесь — на случай прямого доступа к :5173: /api и /sanctum → nginx.
    proxy: {
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost',
        changeOrigin: true,
      },
    },
  },
})
