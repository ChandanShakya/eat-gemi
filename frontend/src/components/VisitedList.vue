<template>
  <div class="space-y-3">
    <!-- Empty State -->
    <div v-if="!visitedPlaces.length" class="text-center py-6">
      <div class="text-gray-400 text-3xl mb-2">📍</div>
      <p class="text-gray-600 text-sm">No visits yet</p>
      <p class="text-gray-500 text-xs">Mark restaurants as visited to build your history</p>
    </div>

    <!-- Visited Places List -->
    <div v-else class="space-y-3 max-h-96 overflow-y-auto">
      <div
        v-for="place in sortedVisitedPlaces"
        :key="place.id"
        class="border border-gray-200 rounded-lg p-3 hover:shadow-sm transition-shadow"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1 min-w-0">
            <h4 class="font-medium text-gray-900 truncate">{{ place.name }}</h4>
            <p class="text-sm text-gray-600 mt-1">
              ✅ Visited {{ formatVisitDate(place.visited_at) }}
            </p>
            
            <!-- Menu Preview -->
            <div v-if="place.menu_image_url || (place.menu_table && place.menu_table.length)" 
                 class="mt-2">
              <button 
                @click="toggleMenuPreview(place.id)"
                class="text-xs text-blue-600 hover:text-blue-800 underline"
              >
                {{ showMenuPreviews[place.id] ? 'Hide Menu' : 'Show Menu' }}
              </button>
              
              <!-- Menu Content -->
              <div v-if="showMenuPreviews[place.id]" class="mt-2">
                <!-- Menu Image -->
                <img v-if="place.menu_image_url" 
                     :src="place.menu_image_url"
                     :alt="`Menu for ${place.name}`"
                     class="w-full h-24 object-cover rounded cursor-pointer"
                     @click="openMenuModal(place)"
                     loading="lazy"
                />
                
                <!-- Menu Table Preview -->
                <div v-if="place.menu_table && place.menu_table.length" 
                     class="mt-2 text-xs">
                  <div class="bg-gray-50 rounded p-2">
                    <div v-for="(item, index) in place.menu_table.slice(0, 2)" 
                         :key="index"
                         class="flex justify-between items-center py-1">
                      <span class="text-gray-700">{{ item.name || item.item }}</span>
                      <span class="text-gray-600">{{ item.price }}</span>
                    </div>
                    <div v-if="place.menu_table.length > 2" 
                         class="text-gray-500 text-center pt-1 border-t border-gray-200 mt-1">
                      +{{ place.menu_table.length - 2 }} more items
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sync Status (for offline visits) -->
            <div v-if="place.synced === false" class="mt-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                📡 Pending sync
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center space-x-2 ml-3">
            <!-- Google Maps Link -->
            <a :href="getGoogleMapsUrl(place)" 
               target="_blank"
               class="text-blue-600 hover:text-blue-800 text-sm"
               title="View on Google Maps">
              📍
            </a>
            
            <!-- Remove Button -->
            <button 
              @click="confirmRemove(place)"
              class="text-red-600 hover:text-red-800 text-sm"
              title="Remove from visited"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Remove Confirmation Modal -->
    <div v-if="showRemoveModal" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
         @click="cancelRemove">
      <div class="bg-white rounded-lg max-w-sm w-full p-6" @click.stop>
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Remove from visited?</h3>
        <p class="text-gray-600 mb-4">
          Are you sure you want to remove <strong>{{ placeToRemove?.name }}</strong> from your visited places?
        </p>
        <div class="flex space-x-3">
          <button @click="cancelRemove" 
                  class="flex-1 btn btn-secondary">
            Cancel
          </button>
          <button @click="executeRemove" 
                  class="flex-1 btn bg-red-500 text-white hover:bg-red-600">
            Remove
          </button>
        </div>
      </div>
    </div>

    <!-- Menu Modal -->
    <div v-if="showMenuModal && selectedPlace" 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
         @click="closeMenuModal">
      <div class="bg-white rounded-lg max-w-2xl max-h-[80vh] overflow-auto" 
           @click.stop>
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="text-lg font-semibold">{{ selectedPlace.name }} - Menu</h3>
          <button @click="closeMenuModal" 
                  class="text-gray-400 hover:text-gray-600">
            ✕
          </button>
        </div>
        <div class="p-4">
          <img 
            :src="selectedPlace.menu_image_url" 
            :alt="`Menu for ${selectedPlace.name}`"
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

// Props
const props = defineProps({
  visitedPlaces: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['remove-visited'])

// Reactive state
const showMenuPreviews = ref({})
const showRemoveModal = ref(false)
const placeToRemove = ref(null)
const showMenuModal = ref(false)
const selectedPlace = ref(null)

// Computed
const sortedVisitedPlaces = computed(() => {
  return [...props.visitedPlaces].sort((a, b) => 
    new Date(b.visited_at) - new Date(a.visited_at)
  )
})

// Methods
const formatVisitDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'today'
  } else if (diffDays === 2) {
    return 'yesterday'
  } else if (diffDays <= 7) {
    return `${diffDays - 1} days ago`
  } else {
    return date.toLocaleDateString()
  }
}

const toggleMenuPreview = (placeId) => {
  showMenuPreviews.value[placeId] = !showMenuPreviews.value[placeId]
}

const getGoogleMapsUrl = (place) => {
  if (place.place_id) {
    return `https://www.google.com/maps/place/?q=place_id:${place.place_id}`
  }
  
  const query = encodeURIComponent(place.name)
  return `https://www.google.com/maps/search/${query}/@${place.lat},${place.lng},15z`
}

const confirmRemove = (place) => {
  placeToRemove.value = place
  showRemoveModal.value = true
}

const cancelRemove = () => {
  showRemoveModal.value = false
  placeToRemove.value = null
}

const executeRemove = () => {
  if (placeToRemove.value) {
    emit('remove-visited', placeToRemove.value.id)
  }
  cancelRemove()
}

const openMenuModal = (place) => {
  selectedPlace.value = place
  showMenuModal.value = true
}

const closeMenuModal = () => {
  showMenuModal.value = false
  selectedPlace.value = null
}
</script>
