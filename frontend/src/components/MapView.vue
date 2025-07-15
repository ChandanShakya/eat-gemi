<template>
  <div class="relative">
    <div ref="mapContainer" class="map-container rounded-lg border border-gray-300"></div>
    
    <!-- Loading Overlay -->
    <div v-if="isLoading" 
         class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
      <div class="text-center">
        <div class="spinner mx-auto mb-2"></div>
        <p class="text-sm text-gray-600">Loading map...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" 
         class="absolute inset-0 bg-gray-50 flex items-center justify-center rounded-lg">
      <div class="text-center">
        <div class="text-4xl mb-2">🗺️</div>
        <p class="text-sm text-gray-600">{{ error }}</p>
      </div>
    </div>

    <!-- Offline State -->
    <div v-if="isOffline && !map" 
         class="absolute inset-0 bg-gray-50 flex items-center justify-center rounded-lg">
      <div class="text-center">
        <div class="text-4xl mb-2">📍</div>
        <p class="text-sm text-gray-600">Map unavailable offline</p>
        <p class="text-xs text-gray-500 mt-1">Connect to internet to view map</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'

// Props
const props = defineProps({
  restaurants: {
    type: Array,
    default: () => []
  },
  visitedPlaces: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['mark-visited'])

// Reactive state
const mapContainer = ref(null)
const map = ref(null)
const isLoading = ref(true)
const error = ref(null)
const isOffline = ref(!navigator.onLine)
const markers = ref([])
const infoWindow = ref(null)

// Google Maps API key
const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

// Methods
const loadGoogleMapsScript = () => {
  return new Promise((resolve, reject) => {
    if (window.google && window.google.maps) {
      resolve(window.google.maps)
      return
    }

    if (isOffline.value) {
      reject(new Error('Cannot load map while offline'))
      return
    }

    const script = document.createElement('script')
    script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places`
    script.async = true
    script.defer = true
    
    script.onload = () => {
      resolve(window.google.maps)
    }
    
    script.onerror = () => {
      reject(new Error('Failed to load Google Maps'))
    }
    
    document.head.appendChild(script)
  })
}

const initializeMap = async () => {
  if (!mapContainer.value) return

  try {
    isLoading.value = true
    error.value = null

    const google = await loadGoogleMapsScript()
    
    // Default to a central location if no restaurants
    const defaultCenter = { lat: 40.7128, lng: -74.0060 } // New York
    
    map.value = new google.Map(mapContainer.value, {
      zoom: 13,
      center: defaultCenter,
      styles: [
        {
          featureType: 'poi.business',
          stylers: [{ visibility: 'off' }]
        }
      ]
    })

    infoWindow.value = new google.InfoWindow()
    
    // Add markers for restaurants
    updateMarkers()
    
  } catch (err) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}

const updateMarkers = () => {
  if (!map.value || !window.google) return

  // Clear existing markers
  markers.value.forEach(marker => marker.setMap(null))
  markers.value = []

  const bounds = new window.google.maps.LatLngBounds()
  let hasValidMarkers = false

  // Add restaurant markers
  props.restaurants.forEach(restaurant => {
    const position = getRestaurantPosition(restaurant)
    if (!position) return

    const isVisited = props.visitedPlaces.some(
      place => place.place_id === restaurant.place_id
    )

    const marker = new window.google.maps.Marker({
      position,
      map: map.value,
      title: restaurant.name,
      icon: {
        url: isVisited 
          ? 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createMarkerSVG('#10b981', '✅'))
          : 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createMarkerSVG('#ef4444', '🍽️')),
        scaledSize: new window.google.maps.Size(40, 40),
        anchor: new window.google.maps.Point(20, 40)
      }
    })

    marker.addListener('click', () => {
      const content = createInfoWindowContent(restaurant, isVisited)
      infoWindow.value.setContent(content)
      infoWindow.value.open(map.value, marker)
    })

    markers.value.push(marker)
    bounds.extend(position)
    hasValidMarkers = true
  })

  // Add visited places markers (if not already shown as restaurants)
  props.visitedPlaces.forEach(place => {
    const isInRestaurants = props.restaurants.some(r => r.place_id === place.place_id)
    if (isInRestaurants) return

    const position = { lat: parseFloat(place.lat), lng: parseFloat(place.lng) }
    if (!position.lat || !position.lng) return

    const marker = new window.google.maps.Marker({
      position,
      map: map.value,
      title: place.name,
      icon: {
        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(createMarkerSVG('#6b7280', '📍')),
        scaledSize: new window.google.maps.Size(35, 35),
        anchor: new window.google.maps.Point(17.5, 35)
      }
    })

    marker.addListener('click', () => {
      const content = createVisitedInfoWindowContent(place)
      infoWindow.value.setContent(content)
      infoWindow.value.open(map.value, marker)
    })

    markers.value.push(marker)
    bounds.extend(position)
    hasValidMarkers = true
  })

  // Fit map to markers
  if (hasValidMarkers) {
    map.value.fitBounds(bounds)
    
    // Ensure minimum zoom level
    window.google.maps.event.addListenerOnce(map.value, 'bounds_changed', () => {
      if (map.value.getZoom() > 15) {
        map.value.setZoom(15)
      }
    })
  }
}

const getRestaurantPosition = (restaurant) => {
  if (restaurant.geometry?.location?.lat && restaurant.geometry?.location?.lng) {
    return {
      lat: restaurant.geometry.location.lat,
      lng: restaurant.geometry.location.lng
    }
  }
  
  if (restaurant.lat && restaurant.lng) {
    return {
      lat: parseFloat(restaurant.lat),
      lng: parseFloat(restaurant.lng)
    }
  }
  
  return null
}

const createMarkerSVG = (color, emoji) => {
  return `
    <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
      <circle cx="20" cy="15" r="12" fill="${color}" stroke="white" stroke-width="2"/>
      <text x="20" y="20" text-anchor="middle" font-size="12" fill="white">${emoji}</text>
      <path d="M20 27 L15 40 L25 40 Z" fill="${color}"/>
    </svg>
  `
}

const createInfoWindowContent = (restaurant, isVisited) => {
  const rating = restaurant.rating ? `⭐ ${restaurant.rating}` : ''
  const priceLevel = restaurant.price_level ? '$'.repeat(restaurant.price_level) : ''
  
  return `
    <div class="p-3 max-w-xs">
      <h3 class="font-semibold text-gray-900 mb-1">${restaurant.name}</h3>
      <p class="text-sm text-gray-600 mb-2">${restaurant.vicinity || restaurant.formatted_address || ''}</p>
      <div class="flex items-center space-x-2 mb-3">
        ${rating ? `<span class="text-sm">${rating}</span>` : ''}
        ${priceLevel ? `<span class="text-sm text-gray-600">${priceLevel}</span>` : ''}
      </div>
      <button 
        onclick="window.markRestaurantAsVisited('${restaurant.place_id}')"
        ${isVisited ? 'disabled' : ''}
        class="px-3 py-1 text-sm rounded ${
          isVisited 
            ? 'bg-green-100 text-green-700 cursor-not-allowed' 
            : 'bg-green-500 text-white hover:bg-green-600'
        }"
      >
        ${isVisited ? '✅ Visited' : '📍 Mark as Visited'}
      </button>
    </div>
  `
}

const createVisitedInfoWindowContent = (place) => {
  const visitedDate = new Date(place.visited_at).toLocaleDateString()
  
  return `
    <div class="p-3 max-w-xs">
      <h3 class="font-semibold text-gray-900 mb-1">${place.name}</h3>
      <p class="text-sm text-gray-600">✅ Visited on ${visitedDate}</p>
    </div>
  `
}

const updateNetworkStatus = () => {
  isOffline.value = !navigator.onLine
}

// Global function for info window buttons
window.markRestaurantAsVisited = (placeId) => {
  const restaurant = props.restaurants.find(r => r.place_id === placeId)
  if (restaurant) {
    emit('mark-visited', restaurant)
  }
}

// Watchers
watch(() => props.restaurants, () => {
  nextTick(() => {
    if (map.value) {
      updateMarkers()
    }
  })
}, { deep: true })

watch(() => props.visitedPlaces, () => {
  nextTick(() => {
    if (map.value) {
      updateMarkers()
    }
  })
}, { deep: true })

// Lifecycle
onMounted(() => {
  // Monitor network status
  window.addEventListener('online', updateNetworkStatus)
  window.addEventListener('offline', updateNetworkStatus)
  
  // Initialize map if online
  if (!isOffline.value) {
    initializeMap()
  } else {
    isLoading.value = false
  }
})

onUnmounted(() => {
  window.removeEventListener('online', updateNetworkStatus)
  window.removeEventListener('offline', updateNetworkStatus)
  
  // Clean up global function
  delete window.markRestaurantAsVisited
})
</script>
