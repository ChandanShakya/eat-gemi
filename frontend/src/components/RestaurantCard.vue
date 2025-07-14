<template>
  <div class="card p-4 hover:shadow-md transition-shadow duration-200">
    <div class="flex flex-col space-y-3">
      <!-- Restaurant Header -->
      <div class="flex justify-between items-start">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-900 text-lg">{{ restaurant.name }}</h3>
          <p v-if="restaurant.vicinity || restaurant.formatted_address" 
             class="text-sm text-gray-600 mt-1">
            {{ restaurant.vicinity || restaurant.formatted_address }}
          </p>
        </div>
        <div class="flex items-center space-x-2 ml-4">
          <!-- Rating -->
          <div v-if="restaurant.rating" class="flex items-center space-x-1">
            <span class="text-yellow-400">⭐</span>
            <span class="text-sm font-medium">{{ restaurant.rating }}</span>
          </div>
          <!-- Price Level -->
          <div v-if="restaurant.price_level" class="text-sm text-gray-600">
            {{ '$'.repeat(restaurant.price_level) }}
          </div>
        </div>
      </div>

      <!-- Restaurant Details -->
      <div class="space-y-2">
        <!-- Cuisine Type -->
        <div v-if="restaurant.types && restaurant.types.length" class="flex flex-wrap gap-1">
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

        <!-- Menu Information -->
        <div v-if="restaurant.menu_image_url || restaurant.menu_table" class="space-y-2">
          <h4 class="font-medium text-gray-900">Menu</h4>
          
          <!-- Menu Image -->
          <div v-if="restaurant.menu_image_url" class="relative">
            <img 
              :src="restaurant.menu_image_url" 
              :alt="`Menu for ${restaurant.name}`"
              class="w-full h-32 object-cover rounded-lg cursor-pointer"
              @click="showMenuModal = true"
              loading="lazy"
            />
            <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all duration-200 rounded-lg flex items-center justify-center cursor-pointer"
                 @click="showMenuModal = true">
              <span class="text-white opacity-0 hover:opacity-100 transition-opacity">
                🔍 View Full Menu
              </span>
            </div>
          </div>

          <!-- Menu Table -->
          <div v-if="restaurant.menu_table && restaurant.menu_table.length" class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Item</th>
                  <th class="px-3 py-2 text-left font-medium text-gray-700">Price</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(item, index) in displayMenuItems" :key="index">
                  <td class="px-3 py-2 text-gray-900">{{ item.name || item.item }}</td>
                  <td class="px-3 py-2 text-gray-700">{{ item.price }}</td>
                </tr>
              </tbody>
            </table>
            <button v-if="restaurant.menu_table.length > 3" 
                    @click="showFullMenu = !showFullMenu"
                    class="text-green-600 text-sm mt-2 hover:underline">
              {{ showFullMenu ? 'Show Less' : `Show ${restaurant.menu_table.length - 3} More Items` }}
            </button>
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
  return restaurantStore.visitedPlaces.some(
    place => place.place_id === props.restaurant.place_id
  )
})

const googleMapsUrl = computed(() => {
  if (props.restaurant.place_id) {
    return `https://www.google.com/maps/place/?q=place_id:${props.restaurant.place_id}`
  }
  const query = encodeURIComponent(`${props.restaurant.name} ${props.restaurant.vicinity || ''}`)
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

// Methods
const formatType = (type) => {
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const markAsVisited = () => {
  if (!isVisited.value) {
    emit('mark-visited', props.restaurant)
  }
}
</script>
