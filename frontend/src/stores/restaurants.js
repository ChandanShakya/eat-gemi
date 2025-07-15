import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL

export const useRestaurantStore = defineStore('restaurants', () => {
  // State
  const currentRecommendations = ref([])
  const visitedPlaces = ref([])
  const isLoading = ref(false)
  const error = ref(null)
  const currentCity = ref('')
  const isOffline = ref(!navigator.onLine)

  // Computed
  const hasRecommendations = computed(() => currentRecommendations.value.length > 0)
  const visitedCount = computed(() => visitedPlaces.value.length)
  
  // Cache keys for offline storage
  const CACHE_KEYS = {
    recommendations: 'eatgemi_recommendations',
    visited: 'eatgemi_visited',
    city: 'eatgemi_current_city'
  }

  // Safe localStorage operations
  const safeLocalStorage = {
    setItem: (key, value) => {
      try {
        localStorage.setItem(key, value)
      } catch (error) {
        console.warn('Failed to save to localStorage:', error.message)
      }
    },
    getItem: (key) => {
      try {
        return localStorage.getItem(key)
      } catch (error) {
        console.warn('Failed to read from localStorage:', error.message)
        return null
      }
    },
    removeItem: (key) => {
      try {
        localStorage.removeItem(key)
      } catch (error) {
        console.warn('Failed to remove from localStorage:', error.message)
      }
    }
  }

  // Actions
  const getRecommendations = async (city) => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get(`${API_BASE_URL}/recommend`, {
        params: { city }
      })
      
      currentRecommendations.value = response.data.restaurants || []
      currentCity.value = city
      
      // Cache for offline use
      safeLocalStorage.setItem(CACHE_KEYS.recommendations, JSON.stringify(currentRecommendations.value))
      safeLocalStorage.setItem(CACHE_KEYS.city, city)
      
      return { success: true }
    } catch (err) {
      console.error('API Error:', err.response?.data || err.message)
      
      error.value = err.response?.data?.message || 'Failed to get recommendations. Please try again.'
      
      // Try to load from cache if offline
      if (!navigator.onLine) {
        loadFromCache()
      }
      
      return { success: false, error: error.value }
    } finally {
      isLoading.value = false
    }
  }

  const markAsVisited = async (restaurant) => {
    try {
      const visitData = {
        place_id: restaurant.place_id,
        name: restaurant.name,
        lat: restaurant.geometry?.location?.lat || restaurant.lat,
        lng: restaurant.geometry?.location?.lng || restaurant.lng,
        menu_image_url: restaurant.menu_image_url || null,
        menu_table: restaurant.menu_table || null,
        visited_at: new Date().toISOString()
      }

      if (!isOffline.value) {
        const response = await axios.post(`${API_BASE_URL}/visited`, visitData)
        const visitedPlace = response.data
        
        // Add to local state
        visitedPlaces.value.unshift(visitedPlace)
      } else {
        // Add to local cache for sync later
        const tempVisited = { ...visitData, id: Date.now(), synced: false }
        visitedPlaces.value.unshift(tempVisited)
      }
      
      // Update cache
      safeLocalStorage.setItem(CACHE_KEYS.visited, JSON.stringify(visitedPlaces.value))
      
      // Get alternative recommendations after marking as visited
      if (currentCity.value && !isOffline.value) {
        await getAlternativeRecommendations()
      }
      
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to mark as visited'
      return { success: false, error: error.value }
    }
  }

  const getAlternativeRecommendations = async () => {
    if (!currentCity.value) return
    
    try {
      const response = await axios.get(`${API_BASE_URL}/recommend`, {
        params: { 
          city: currentCity.value,
          alternative: true,
          visited_places: visitedPlaces.value.map(p => p.place_id).join(',')
        }
      })
      
      // Add new recommendations to current list
      const newRecommendations = response.data.restaurants || []
      currentRecommendations.value = [
        ...currentRecommendations.value.filter(r => 
          !visitedPlaces.value.some(v => v.place_id === r.place_id)
        ),
        ...newRecommendations
      ]
      
      // Update cache
      safeLocalStorage.setItem(CACHE_KEYS.recommendations, JSON.stringify(currentRecommendations.value))
      
    } catch {
      // Failed to get alternative recommendations
    }
  }

  const loadVisitedPlaces = async () => {
    if (isOffline.value) {
      loadVisitedFromCache()
      return
    }
    
    try {
      const response = await axios.get(`${API_BASE_URL}/visited`)
      visitedPlaces.value = response.data.data || []
      
      // Update cache
      safeLocalStorage.setItem(CACHE_KEYS.visited, JSON.stringify(visitedPlaces.value))
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load visited places'
      loadVisitedFromCache()
    }
  }

  const removeFromVisited = async (visitedId) => {
    try {
      if (!isOffline.value) {
        await axios.delete(`${API_BASE_URL}/visited/${visitedId}`)
      }
      
      // Remove from local state
      visitedPlaces.value = visitedPlaces.value.filter(place => place.id !== visitedId)
      
      // Update cache
      safeLocalStorage.setItem(CACHE_KEYS.visited, JSON.stringify(visitedPlaces.value))
      
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to remove from visited'
      return { success: false, error: error.value }
    }
  }

  const loadFromCache = () => {
    try {
      // Load recommendations
      const cachedRecommendations = safeLocalStorage.getItem(CACHE_KEYS.recommendations)
      if (cachedRecommendations) {
        currentRecommendations.value = JSON.parse(cachedRecommendations)
      }
      
      // Load city
      const cachedCity = safeLocalStorage.getItem(CACHE_KEYS.city)
      if (cachedCity) {
        currentCity.value = cachedCity
      }
      
      loadVisitedFromCache()
    } catch (error) {
      console.warn('Failed to load from cache:', error.message)
    }
  }

  const loadVisitedFromCache = () => {
    try {
      const cachedVisited = safeLocalStorage.getItem(CACHE_KEYS.visited)
      if (cachedVisited) {
        visitedPlaces.value = JSON.parse(cachedVisited)
      }
    } catch (error) {
      console.warn('Failed to load visited places from cache:', error.message)
    }
  }

  const syncOfflineData = async () => {
    if (isOffline.value) return
    
    const unsyncedVisited = visitedPlaces.value.filter(place => !place.synced)
    
    for (const place of unsyncedVisited) {
      try {
        const response = await axios.post(`${API_BASE_URL}/visited`, place)
        // Update with server response
        const index = visitedPlaces.value.findIndex(p => p.id === place.id)
        if (index !== -1) {
          visitedPlaces.value[index] = { ...response.data, synced: true }
        }
      } catch {
        // Failed to sync visited place
      }
    }
    
    // Update cache
    safeLocalStorage.setItem(CACHE_KEYS.visited, JSON.stringify(visitedPlaces.value))
  }

  const updateOfflineStatus = (online) => {
    isOffline.value = !online
    
    if (online) {
      // Sync offline data when coming back online
      syncOfflineData()
    }
  }

  const clearError = () => {
    error.value = null
  }

  const clearRecommendations = () => {
    currentRecommendations.value = []
    currentCity.value = ''
    safeLocalStorage.removeItem(CACHE_KEYS.recommendations)
    safeLocalStorage.removeItem(CACHE_KEYS.city)
  }

  return {
    // State
    currentRecommendations,
    visitedPlaces,
    isLoading,
    error,
    currentCity,
    isOffline,
    
    // Computed
    hasRecommendations,
    visitedCount,
    
    // Actions
    getRecommendations,
    markAsVisited,
    loadVisitedPlaces,
    removeFromVisited,
    loadFromCache,
    updateOfflineStatus,
    clearError,
    clearRecommendations
  }
})
