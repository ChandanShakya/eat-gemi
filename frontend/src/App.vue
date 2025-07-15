<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Offline Banner -->
    <div v-if="!isOnline" class="offline-banner">
      📡 You're offline. Some features may be limited.
    </div>

    <!-- PWA Install Prompt -->
    <div v-if="showInstallPrompt" class="pwa-install-prompt">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold">Install EatGemi</h3>
          <p class="text-sm opacity-90">Get quick access and offline features</p>
        </div>
        <div class="flex gap-2">
          <button @click="dismissInstallPrompt" class="px-3 py-1 text-sm bg-white/20 rounded">
            Later
          </button>
          <button @click="installPWA" class="px-3 py-1 text-sm bg-white text-green-500 rounded font-medium">
            Install
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <RouterView />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

// Stores
const authStore = useAuthStore()

// Reactive state
const isOnline = ref(navigator.onLine)
const showInstallPrompt = ref(false)
let deferredPrompt = null

// PWA Install functionality
const installPWA = async () => {
  if (deferredPrompt) {
    deferredPrompt.prompt()
    await deferredPrompt.userChoice
    deferredPrompt = null
    showInstallPrompt.value = false
  }
}

const dismissInstallPrompt = () => {
  showInstallPrompt.value = false
  localStorage.setItem('pwa-install-dismissed', 'true')
}

// Network status monitoring
const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine
}

onMounted(() => {
  // Initialize auth store
  authStore.initializeAuth()

  // Network status listeners
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)

  // PWA install prompt
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e
    
    // Show install prompt if not previously dismissed
    if (!localStorage.getItem('pwa-install-dismissed')) {
      showInstallPrompt.value = true
    }
  })

  // PWA installed event
  window.addEventListener('appinstalled', () => {
    showInstallPrompt.value = false
  })
})
</script>
