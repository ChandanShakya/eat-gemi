<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-green-600">MapGemi</h1>
            <p class="ml-3 text-gray-600 hidden sm:block">AI Location Finder</p>
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
          <h2 class="text-xl font-semibold text-gray-900 mb-4">Find Locations</h2>
          <form @submit.prevent="searchRestaurants" class="flex gap-4">
            <div class="flex-1">
              <input
                v-model="searchPrompt"
                type="text"
                placeholder="Enter what you're looking for (e.g., 'Italian restaurants in Rome', 'coffee shops near me', 'hotels in Paris')"
                class="input"
                :disabled="restaurantStore.isLoading"
              />
            </div>
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="restaurantStore.isLoading"
            >
              <span v-if="restaurantStore.isLoading" class="flex items-center">
                <div class="spinner mr-2"></div>
                Searching...
              </span>
              <span v-else>
                🔍 Search
              </span>
            </button>
            <!-- Test button to bypass form validation -->
            <button 
              type="button" 
              @click="testSearch"
              class="btn bg-orange-500 text-white hover:bg-orange-600"
              :disabled="restaurantStore.isLoading"
            >
              🧪 Test Search
            </button>
          </form>
          
          <!-- Error Message -->
          <div v-if="restaurantStore.error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700 text-sm">{{ restaurantStore.error }}</p>
            <button @click="restaurantStore.clearError" class="text-red-600 text-xs underline mt-1">
              Dismiss
            </button>
          </div>

          <!-- Success Message -->
          <div v-if="restaurantStore.currentPrompt && !restaurantStore.isLoading && !restaurantStore.error" 
               class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-700 text-sm">
              ✅ Found {{ restaurantStore.currentRecommendations.length }} locations for your search!
            </p>
          </div>
        </div>
      </div>

      <!-- Results Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Locations List -->
        <div class="lg:col-span-2">
          <div v-if="restaurantStore.hasRecommendations" class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">
              Search Results
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
            <div class="text-gray-400 text-6xl mb-4">🗺️</div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
              Ready to find amazing places?
            </h3>
            <p class="text-gray-600 mb-4">
              Enter what you're looking for above to get AI-powered location recommendations.
            </p>
          </div>
          
          <!-- Loading State -->
          <div v-if="restaurantStore.isLoading" class="text-center py-12">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-600">Finding locations for you...</p>
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
const searchPrompt = ref('')

// Methods
const searchRestaurants = async () => {
  if (!searchPrompt.value || !searchPrompt.value.trim()) {
    restaurantStore.error = 'Please enter what you\'re looking for'
    return
  }
  
  const result = await restaurantStore.getRecommendations(searchPrompt.value.trim())
  
  if (!result.success) {
    // Error is already set in the store
  }
}

const testSearch = async () => {
  searchPrompt.value = 'Italian restaurants in Rome'
  await searchRestaurants()
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
  
  // Expose test function for debugging
  window.testEatGemiSearch = testSearch
})
</script>
