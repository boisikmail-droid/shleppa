import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    // VK Tunnel / Mini App iframe open the app via *.vk-apps.com
    allowedHosts: true,
    strictPort: true,
    proxy: {
      '/api': {
        target: process.env.VITE_API_PROXY_TARGET || 'http://localhost:8080',
        changeOrigin: true,
      },
    },
    // HMR through HTTPS tunnel from host machine
    hmr: process.env.VITE_HMR_CLIENT_PORT
      ? {
          clientPort: Number(process.env.VITE_HMR_CLIENT_PORT),
        }
      : undefined,
  },
})
