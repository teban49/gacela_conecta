import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const backendUrl = new URL(env.VITE_BACKEND_URL || 'http://127.0.0.1:8002')
  if (backendUrl.hostname === 'localhost') {
    backendUrl.hostname = '127.0.0.1'
  }

  const apiProxy = {
    '/api': {
      target: backendUrl.toString(),
      changeOrigin: true,
    },
  }

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      host: true,
      port: 5173,
      strictPort: false,
      proxy: apiProxy,
    },
    preview: {
      proxy: apiProxy,
    },
  }
})