<template>
  <div class="card p-4 hover:shadow-md transition-shadow duration-200">
    <div class="flex flex-col space-y-3">
      <!-- Restaurant Header -->
      <div class="flex justify-between items-start">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-900 text-lg">{{ restaurant.name }}</h3>
          <p v-if="restaurant.address || restaurant.vicinity || restaurant.formatted_address" 
             class="text-sm text-gray-600 mt-1">
            {{ restaurant.address || restaurant.vicinity || restaurant.formatted_address }}
          </p>
        </div>
        <div class="flex items-center space-x-2 ml-4">
          <!-- Rating -->
          <div v-if="restaurant.rating" class="flex items-center space-x-1">
            <span class="text-yellow-400">⭐</span>
            <span class="text-sm font-medium">{{ restaurant.rating }}</span>
          </div>
          <!-- Price Level -->
          <div v-if="restaurant.price_level || restaurant.price_range" class="text-sm text-gray-600">
            {{ restaurant.price_range || '$'.repeat(restaurant.price_level) }}
          </div>
        </div>
      </div>

      <!-- Restaurant Details -->
      <div class="space-y-2">
        <!-- Cuisine Type -->
        <div v-if="restaurant.cuisine || (restaurant.types && restaurant.types.length)" class="flex flex-wrap gap-1">
          <span v-if="restaurant.cuisine" 
                class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
            {{ restaurant.cuisine }}
          </span>
          <span v-for="type in displayTypes" :key="type" 
                class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">
            {{ formatType(type) }}
          </span>
        </div>

        <!-- Opening Hours -->
        <div v-if="restaurant.opening_hours" class="text-sm">
          <span :class="restaurant.opening_hours.open_now ? 'text-green-600' : 'text-red-600'">
            {{ restaurant.opening_hours.open_now ? '🟢 Open Now' : '🔴 Closed' }}
          </span>
        </div>

        <!-- Menu Information - ALWAYS DISPLAYED FOR RESTAURANTS -->
        <div v-if="isRestaurant" class="space-y-2">
          <h4 class="font-medium text-gray-900 flex items-center">
            <span class="mr-2">🍽️</span> Menu
            <span v-if="restaurant.menu_note" class="ml-2 text-xs text-gray-500">({{ restaurant.menu_note }})</span>
          </h4>
          
          <!-- Menu Table (Priority 1) -->
          <div v-if="restaurant.menu_table && restaurant.menu_table.length" class="overflow-x-auto bg-gray-50 rounded-lg p-3">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Item</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Price</th>
                  <th v-if="hasDescriptions" class="px-3 py-2 text-left font-medium text-gray-700">Description</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(item, index) in displayMenuItems" :key="index" class="hover:bg-gray-50">
                  <td class="px-3 py-2 text-gray-900 font-medium">{{ item.name || item.item }}</td>
                  <td class="px-3 py-2 text-green-600 font-semibold">{{ item.price }}</td>
                  <td v-if="hasDescriptions" class="px-3 py-2 text-gray-600 text-xs">{{ item.description || '-' }}</td>
                </tr>
              </tbody>
            </table>
            <button v-if="restaurant.menu_table.length > 3" 
                    @click="showFullMenu = !showFullMenu"
                    class="text-blue-600 text-sm mt-2 hover:underline flex items-center">
              {{ showFullMenu ? '� Show Less' : `🔽 Show ${restaurant.menu_table.length - 3} More Items` }}
            </button>
          </div>
          
          <!-- Menu Image (Priority 2) -->
          <div v-else-if="restaurant.menu_image_url" class="relative bg-gray-50 rounded-lg p-3">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm text-gray-600">Menu Image</span>
              <button 
                @click="openMenuImage"
                class="text-blue-600 text-sm hover:underline">
                🔍 View Full Size
              </button>
            </div>
            
            <!-- Google Photos Search Link -->
            <div v-if="restaurant.menu_image_url.includes('google.com/search')" 
                 class="text-center p-4 border-2 border-dashed border-gray-300 rounded">
              <p class="text-sm text-gray-600 mb-2">📸 Search for menu images</p>
              <a :href="restaurant.menu_image_url" 
                 target="_blank"
                 class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors">
                🔍 Search Google Images
              </a>
            </div>
            
            <!-- Direct Image Link -->
            <div v-else>
              <img 
                :src="restaurant.menu_image_url" 
                :alt="`Menu for ${restaurant.name}`"
                class="w-full h-40 object-cover rounded cursor-pointer hover:opacity-90 transition-opacity"
                @click="showMenuModal = true"
                @error="handleImageError"
                loading="lazy"
              />
            </div>
          </div>
          
          <!-- Menu Link (Priority 3) -->
          <div v-else-if="restaurant.menu_link || restaurant.menu_url" class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">📋 Menu Link</span>
              <a :href="restaurant.menu_link || restaurant.menu_url" 
                 target="_blank"
                 class="text-blue-600 hover:text-blue-800 underline text-sm">
                🔗 View Menu
              </a>
            </div>
          </div>
          
          <!-- Fallback: Google Photos Search (Always available for restaurants) -->
          <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-yellow-800 font-medium">📸 Find Menu Images</p>
                <p class="text-xs text-yellow-600 mt-1">Search Google Photos for menu images</p>
              </div>
              <a :href="generateGooglePhotosUrl()" 
                 target="_blank"
                 class="px-3 py-1 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700 transition-colors">
                Search Images
              </a>
            </div>
          </div>
        </div>

        <!-- Non-Restaurant Location Info -->
        <div v-else class="space-y-2">
          <div v-if="restaurant.features && restaurant.features.length" class="flex flex-wrap gap-1">
            <span v-for="feature in restaurant.features" :key="feature" 
                  class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
              {{ formatFeature(feature) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-between items-center pt-3 border-t border-gray-100">
        <div class="flex space-x-2">
          <!-- Google Maps Link -->
          <a v-if="restaurant.place_id" 
             :href="googleMapsUrl" 
             target="_blank"
             class="text-sm text-blue-600 hover:text-blue-800 underline">
            📍 View on Maps
          </a>
          
          <!-- Website Link -->
          <a v-if="restaurant.website" 
             :href="restaurant.website" 
             target="_blank"
             class="text-sm text-blue-600 hover:text-blue-800 underline">
            🌐 Website
          </a>
        </div>

        <!-- Visit Button -->
        <button 
          @click="markAsVisited"
          :disabled="isVisited"
          :class="[
            'btn text-sm',
            isVisited 
              ? 'bg-green-100 text-green-700 cursor-not-allowed' 
              : 'btn-primary'
          ]"
        >
          {{ isVisited ? '✅ Visited' : '📍 Mark as Visited' }}
        </button>
      </div>
    </div>

    <!-- Menu Modal -->
    <div v-if="showMenuModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
         @click="showMenuModal = false">
      <div class="bg-white rounded-lg max-w-2xl max-h-[80vh] overflow-auto" 
           @click.stop>
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="text-lg font-semibold">{{ restaurant.name }} - Menu</h3>
          <button @click="showMenuModal = false" 
                  class="text-gray-400 hover:text-gray-600">
            ✕
          </button>
        </div>
        <div class="p-4">
          <img 
            :src="restaurant.menu_image_url" 
            :alt="`Menu for ${restaurant.name}`"
            class="w-full h-auto"
            loading="lazy"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRestaurantStore } from '@/stores/restaurants'

// Props
const props = defineProps({
  restaurant: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['mark-visited'])

// Store
const restaurantStore = useRestaurantStore()

// Reactive state
const showMenuModal = ref(false)
const showFullMenu = ref(false)

// Computed
const isVisited = computed(() => {
  const visitedPlacesArray = Array.isArray(restaurantStore.visitedPlaces) ? restaurantStore.visitedPlaces : []
  
  if (props.restaurant.place_id) {
    return visitedPlacesArray.some(
      place => place.place_id === props.restaurant.place_id
    )
  }
  // For restaurants without place_id (from Gemini), compare by name and address
  return visitedPlacesArray.some(place => {
    const restaurantAddress = props.restaurant.address || props.restaurant.vicinity || ''
    const placeAddress = place.address || ''
    
    // Safe string comparison
    if (!restaurantAddress || !placeAddress) {
      return place.name === props.restaurant.name
    }
    
    const restaurantAddressPart = restaurantAddress.split(',')[0] || restaurantAddress
    return place.name === props.restaurant.name && 
           placeAddress.toLowerCase().includes(restaurantAddressPart.toLowerCase())
  })
})

const googleMapsUrl = computed(() => {
  if (props.restaurant.place_id) {
    return `https://www.google.com/maps/place/?q=place_id:${props.restaurant.place_id}`
  }
  const address = props.restaurant.address || props.restaurant.vicinity || ''
  const query = encodeURIComponent(`${props.restaurant.name} ${address}`)
  return `https://www.google.com/maps/search/${query}`
})

const displayTypes = computed(() => {
  if (!props.restaurant.types) return []
  
  // Filter out generic types and limit to 3
  const relevantTypes = props.restaurant.types
    .filter(type => !['establishment', 'point_of_interest', 'food'].includes(type))
    .slice(0, 3)
  
  return relevantTypes
})

const displayMenuItems = computed(() => {
  if (!props.restaurant.menu_table) return []
  
  return showFullMenu.value 
    ? props.restaurant.menu_table 
    : props.restaurant.menu_table.slice(0, 3)
})

const isRestaurant = computed(() => {
  const type = (props.restaurant.type || '').toLowerCase()
  const foodTypes = ['restaurant', 'cafe', 'bar', 'bistro', 'diner', 'pizzeria', 'bakery', 'food', 'eatery']
  
  return foodTypes.some(foodType => type.includes(foodType)) || 
         (props.restaurant.cuisine || props.restaurant.menu_table || props.restaurant.menu_image_url)
})

const hasDescriptions = computed(() => {
  if (!props.restaurant.menu_table) return false
  return props.restaurant.menu_table.some(item => item.description && item.description.trim() !== '')
})

// Methods
const formatType = (type) => {
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const markAsVisited = () => {
  if (!isVisited.value) {
    emit('mark-visited', props.restaurant)
  }
}

const generateGooglePhotosUrl = () => {
  const searchQuery = encodeURIComponent(`${props.restaurant.name} menu`)
  return `https://www.google.com/search?tbm=isch&q=${searchQuery}`
}

const openMenuImage = () => {
  if (props.restaurant.menu_image_url) {
    if (props.restaurant.menu_image_url.includes('google.com/search')) {
      window.open(props.restaurant.menu_image_url, '_blank')
    } else {
      showMenuModal.value = true
    }
  }
}

const handleImageError = (event) => {
  // Hide broken image and show Google Photos search instead
  event.target.style.display = 'none'
  // Could set a reactive flag here to show fallback content
}

const formatFeature = (feature) => {
  return feature.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>
