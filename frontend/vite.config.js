import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  server: {
    host: true,
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false
      }
    }
  },
  preview: {
    host: true,
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false
      }
    }
  },
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      injectRegister: 'auto',
      includeAssets: ['favicon.svg', 'favicon-64x64.png', 'apple-touch-icon.png'],
      manifest: {
        name: 'Điểm danh QR',
        short_name: 'Điểm danh QR',
        description: 'Ứng dụng điểm danh sinh viên bằng QR Code',
        theme_color: '#2563eb',
        background_color: '#f1f5f9',
        lang: 'vi',
        display: 'standalone',
        orientation: 'portrait',
        start_url: '/',
        scope: '/',
        icons: [
          {
            src: '/pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          },
          {
            src: '/pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png',
            purpose: 'any maskable'
          }
        ]
      },
      workbox: {
        // Chỉ cache tài nguyên frontend tĩnh
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        // Không can thiệp vào request API Laravel
        navigateFallbackDenylist: [/^\/api/],
        // Dọn dẹp cache cũ khi có bản cập nhật mới
        cleanupOutdatedCaches: true
      }
    })
  ],
})
