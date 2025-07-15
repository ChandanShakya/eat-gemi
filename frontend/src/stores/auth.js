import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const error = ref(null)

  // Computed
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Configure axios defaults
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  // Actions
  const initializeAuth = async () => {
    if (token.value) {
      try {
        // Verify token is still valid by fetching user data
        const response = await axios.get(`${API_BASE_URL}/user`)
        user.value = response.data
      } catch {
        // Token is invalid, clear it
        logout()
      }
    }
  }

  const login = async (credentials) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/login`, credentials)
      const { user: userData, token: authToken } = response.data
      
      user.value = userData
      token.value = authToken
      
      // Store token in localStorage
      localStorage.setItem('auth_token', authToken)
      
      // Set default authorization header
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Login failed'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post(`${API_BASE_URL}/auth/register`, userData)
      const { user: newUser, token: authToken } = response.data
      
      user.value = newUser
      token.value = authToken
      
      // Store token in localStorage
      localStorage.setItem('auth_token', authToken)
      
      // Set default authorization header
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Registration failed'
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    try {
      if (token.value) {
        await axios.post(`${API_BASE_URL}/auth/logout`)
      }
    } catch (err) {
      // eslint-disable-next-line no-console
      console.warn('Logout API call failed:', err)
    } finally {
      // Clear local state regardless of API call success
      user.value = null
      token.value = null
      
      // Clear localStorage
      localStorage.removeItem('auth_token')
      
      // Remove authorization header
      delete axios.defaults.headers.common['Authorization']
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    user,
    token,
    isLoading,
    error,
    
    // Computed
    isAuthenticated,
    
    // Actions
    initializeAuth,
    login,
    register,
    logout,
    clearError
  }
})
