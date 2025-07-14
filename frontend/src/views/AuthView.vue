<template>
  <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-4xl font-bold text-green-600 mb-2">EatGemi</h1>
        <p class="text-gray-600">AI-powered restaurant discovery</p>
      </div>

      <!-- Auth Form Card -->
      <div class="card p-8">
        <div class="flex space-x-1 bg-gray-100 rounded-lg p-1 mb-6">
          <button
            @click="activeTab = 'login'"
            :class="[
              'flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors',
              activeTab === 'login' 
                ? 'bg-white text-gray-900 shadow-sm' 
                : 'text-gray-600 hover:text-gray-900'
            ]"
          >
            Login
          </button>
          <button
            @click="activeTab = 'register'"
            :class="[
              'flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors',
              activeTab === 'register' 
                ? 'bg-white text-gray-900 shadow-sm' 
                : 'text-gray-600 hover:text-gray-900'
            ]"
          >
            Register
          </button>
        </div>

        <!-- Error Message -->
        <div v-if="authStore.error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-red-700 text-sm">{{ authStore.error }}</p>
          <button @click="authStore.clearError" class="text-red-600 text-xs underline mt-1">
            Dismiss
          </button>
        </div>

        <!-- Login Form -->
        <form v-if="activeTab === 'login'" @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">
              Email Address
            </label>
            <input
              id="login-email"
              v-model="loginForm.email"
              type="email"
              class="input"
              placeholder="Enter your email"
              required
              :disabled="authStore.isLoading"
            />
          </div>
          
          <div>
            <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">
              Password
            </label>
            <input
              id="login-password"
              v-model="loginForm.password"
              type="password"
              class="input"
              placeholder="Enter your password"
              required
              :disabled="authStore.isLoading"
            />
          </div>

          <button 
            type="submit" 
            class="w-full btn btn-primary"
            :disabled="authStore.isLoading"
          >
            <span v-if="authStore.isLoading" class="flex items-center justify-center">
              <div class="spinner mr-2"></div>
              Signing in...
            </span>
            <span v-else>Sign In</span>
          </button>
        </form>

        <!-- Register Form -->
        <form v-if="activeTab === 'register'" @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label for="register-name" class="block text-sm font-medium text-gray-700 mb-1">
              Full Name
            </label>
            <input
              id="register-name"
              v-model="registerForm.name"
              type="text"
              class="input"
              placeholder="Enter your full name"
              required
              :disabled="authStore.isLoading"
            />
          </div>

          <div>
            <label for="register-email" class="block text-sm font-medium text-gray-700 mb-1">
              Email Address
            </label>
            <input
              id="register-email"
              v-model="registerForm.email"
              type="email"
              class="input"
              placeholder="Enter your email"
              required
              :disabled="authStore.isLoading"
            />
          </div>
          
          <div>
            <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">
              Password
            </label>
            <input
              id="register-password"
              v-model="registerForm.password"
              type="password"
              class="input"
              placeholder="Create a password (min 8 characters)"
              required
              minlength="8"
              :disabled="authStore.isLoading"
            />
          </div>

          <div>
            <label for="register-password-confirmation" class="block text-sm font-medium text-gray-700 mb-1">
              Confirm Password
            </label>
            <input
              id="register-password-confirmation"
              v-model="registerForm.password_confirmation"
              type="password"
              class="input"
              placeholder="Confirm your password"
              required
              :disabled="authStore.isLoading"
            />
          </div>

          <button 
            type="submit" 
            class="w-full btn btn-primary"
            :disabled="authStore.isLoading || !passwordsMatch"
          >
            <span v-if="authStore.isLoading" class="flex items-center justify-center">
              <div class="spinner mr-2"></div>
              Creating account...
            </span>
            <span v-else>Create Account</span>
          </button>

          <!-- Password match indicator -->
          <div v-if="registerForm.password_confirmation && !passwordsMatch" 
               class="text-sm text-red-600">
            Passwords do not match
          </div>
        </form>
      </div>

      <!-- Features -->
      <div class="text-center space-y-4">
        <h3 class="text-lg font-medium text-gray-900">Why EatGemi?</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
          <div class="flex flex-col items-center">
            <div class="text-2xl mb-2">🤖</div>
            <div class="font-medium">AI-Powered</div>
            <div class="text-gray-600">Smart recommendations</div>
          </div>
          <div class="flex flex-col items-center">
            <div class="text-2xl mb-2">📍</div>
            <div class="font-medium">Location-Based</div>
            <div class="text-gray-600">Find nearby gems</div>
          </div>
          <div class="flex flex-col items-center">
            <div class="text-2xl mb-2">📱</div>
            <div class="font-medium">Offline Ready</div>
            <div class="text-gray-600">Works without internet</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Router and stores
const router = useRouter()
const authStore = useAuthStore()

// Reactive state
const activeTab = ref('login')
const loginForm = ref({
  email: '',
  password: ''
})
const registerForm = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

// Computed
const passwordsMatch = computed(() => {
  return registerForm.value.password === registerForm.value.password_confirmation
})

// Methods
const handleLogin = async () => {
  const result = await authStore.login(loginForm.value)
  
  if (result.success) {
    router.push('/')
  }
}

const handleRegister = async () => {
  if (!passwordsMatch.value) {
    authStore.error = 'Passwords do not match'
    return
  }
  
  const result = await authStore.register(registerForm.value)
  
  if (result.success) {
    router.push('/')
  }
}
</script>
