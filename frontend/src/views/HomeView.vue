<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-green-600">EatGemi</h1>
            <p class="ml-3 text-gray-600 hidden sm:block">AI Restaurant Finder</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-600">
              Welcome, {{ authStore.user?.name }}
            </div>
            <button 
              @click="authStore.logout" 
              class="btn btn-secondary text-sm"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Search Section -->
      <div class="mb-8">
        <div class="card p-6">
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Find Restaurants</h2>
          <form @submit.prevent="searchRestaurants" class="flex gap-4">
            <div class="flex-1">
              <input
                v-model="searchCity"
                type="text"
                placeholder="Enter city name (e.g., New York, Tokyo, London)"
                class="input"
                :disabled="restaurantStore.isLoading"
                required
              />
            </div>
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="restaurantStore.isLoading || !searchCity.trim()"
            >
              <span v-if="restaurantStore.isLoading" class="flex items-center">
                <div class="spinner mr-2"></div>
                Searching...
              </span>
              <span v-else>
                🔍 Search
              </span>
            </button>
          </form>
          
          <!-- Error Message -->
          <div v-if="restaurantStore.error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700 text-sm">{{ restaurantStore.error }}</p>
            <button @click="restaurantStore.clearError" class="text-red-600 text-xs underline mt-1">
              Dismiss
            </button>
          </div>
        </div>
      </div>

      <!-- Results Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Restaurants List -->
        <div class="lg:col-span-2">
          <div v-if="restaurantStore.hasRecommendations" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">
              Restaurants in {{ restaurantStore.currentCity }}
            </h3>
            <div class="grid gap-4">
              <RestaurantCard
                v-for="restaurant in restaurantStore.currentRecommendations"
                :key="restaurant.place_id || restaurant.id"
                :restaurant="restaurant"
                @mark-visited="handleMarkVisited"
              />
            </div>
          </div>
          
          <!-- Empty State -->
          <div v-else-if="!restaurantStore.isLoading" class="text-center py-12">
            <div class="text-gray-400 text-6xl mb-4">🍽️</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
              Ready to find amazing restaurants?
            </h3>
            <p class="text-gray-600 mb-4">
              Enter a city name above to get AI-powered restaurant recommendations.
            </p>
          </div>
          
          <!-- Loading State -->
          <div v-if="restaurantStore.isLoading" class="text-center py-12">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Getting personalized recommendations...</p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Map View -->
          <div class="card p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Map View</h3>
            <MapView 
              :restaurants="restaurantStore.currentRecommendations"
              :visited-places="restaurantStore.visitedPlaces"
              @mark-visited="handleMarkVisited"
            />
          </div>

          <!-- Visited Places -->
          <div class="card p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              Your Visits ({{ restaurantStore.visitedCount }})
            </h3>
            <VisitedList 
              :visited-places="restaurantStore.visitedPlaces"
              @remove-visited="handleRemoveVisited"
            />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRestaurantStore } from '@/stores/restaurants'
import RestaurantCard from '@/components/RestaurantCard.vue'
import MapView from '@/components/MapView.vue'
import VisitedList from '@/components/VisitedList.vue'

// Stores
const authStore = useAuthStore()
const restaurantStore = useRestaurantStore()

// Reactive state
const searchCity = ref('')

// Methods
const searchRestaurants = async () => {
  if (!searchCity.value.trim()) return
  
  const result = await restaurantStore.getRecommendations(searchCity.value.trim())
  
  if (!result.success) {
    // Failed to get recommendations
  }
}

const handleMarkVisited = async (restaurant) => {
  const result = await restaurantStore.markAsVisited(restaurant)
  
  if (result.success) {
    // Show success feedback (could add toast notification here)
  } else {
    // Failed to mark as visited
  }
}

const handleRemoveVisited = async (visitedId) => {
  const result = await restaurantStore.removeFromVisited(visitedId)
  
  if (result.success) {
    // Removed from visited places
  } else {
    // Failed to remove from visited
  }
}

// Lifecycle
onMounted(async () => {
  // Load visited places on component mount
  await restaurantStore.loadVisitedPlaces()
  
  // Load cached data if available
  restaurantStore.loadFromCache()
  
  // Monitor network status
  const updateNetworkStatus = () => {
    restaurantStore.updateOfflineStatus(navigator.onLine)
  }
  
  window.addEventListener('online', updateNetworkStatus)
  window.addEventListener('offline', updateNetworkStatus)
})
</script>
