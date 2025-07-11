# Copilot Instructions - EatGemi AI Restaurant Finder

## Project Architecture Overview

This is a **Vue 3 PWA + Laravel API** project for AI-powered restaurant discovery. The system integrates **Google Gemini AI**, **Google Maps/Places APIs**, and provides offline-capable restaurant recommendations.

### Key Components
- **Frontend**: Vue 3 + Vite PWA (Single Page Application)
- **Backend**: Laravel REST API with MySQL database
- **AI Integration**: Google Gemini API for restaurant recommendations
- **Maps**: Google Maps & Places APIs for location services
- **Authentication**: Laravel Sanctum token-based auth
- **Offline Support**: PWA with service worker caching

## Core Data Flow

1. **User Search** → Location input → **Gemini API** generates restaurant suggestions
2. **Restaurant Display** → **Google Places API** fetches details → **Google Maps** renders pins
3. **Menu Access** → Structured table or image from Places API/Gemini response
4. **Visit Tracking** → Mark visited → Store in Laravel DB → Cache for offline
5. **AI Recommendations** → Post-visit alternative suggestions via Gemini

## Essential File Structure to Create

```
├── backend/                    # Laravel API
│   ├── app/Http/Controllers/
│   │   ├── AuthController.php  # Registration, login, logout
│   │   ├── RecommendController.php # Gemini API integration
│   │   └── VisitedController.php   # CRUD for visited places
│   ├── app/Models/
│   │   ├── User.php
│   │   └── VisitedPlace.php
│   └── routes/api.php          # API endpoints (see SRS section 6.3)
├── frontend/                   # Vue 3 PWA
│   ├── src/components/
│   │   ├── MapView.vue         # Google Maps integration
│   │   ├── RestaurantCard.vue  # Individual restaurant display
│   │   └── VisitedList.vue     # User's visit history
│   ├── src/views/
│   │   ├── HomeView.vue        # Landing + search interface
│   │   └── AuthView.vue        # Login/register forms
│   ├── src/stores/             # Pinia state management
│   │   ├── auth.js             # User authentication state
│   │   └── restaurants.js      # Restaurant data & visited list
│   ├── public/manifest.json    # PWA manifest
│   └── vite.config.js          # Include vite-plugin-pwa
```

## Critical Development Patterns

### API Integration Pattern
- **Gemini API calls**: Always handle in Laravel backend, never expose API keys to frontend
- **Error handling**: Implement exponential backoff for rate limits (NFR requirement)
- **Prompt structure**: Use city-based prompts: `"Suggest 5 restaurants in <<city>> with menu table or image links"`

### State Management (Pinia)
```javascript
// restaurants.js store pattern
const useRestaurantStore = defineStore('restaurants', {
  state: () => ({
    currentRecommendations: [],
    visitedPlaces: [],
    isOffline: false
  }),
  actions: {
    async markAsVisited(place) {
      // Update both backend API and local state
      // Trigger alternative recommendations via Gemini
    }
  }
})
```

### PWA Implementation Requirements
- Use **vite-plugin-pwa** with Workbox
- Cache strategies: visited places, restaurant data, static assets
- Offline indicator banner when network unavailable
- Install prompt for PWA functionality

### Google Maps Integration Pattern
```javascript
// MapView.vue - Key implementation points
- Load Google Maps JavaScript API
- Render restaurant pins with click handlers
- InfoWindow displays: name, menu preview, "Mark Visited" button
- Handle offline fallback (show cached pins only)
```

## Essential API Endpoints (Laravel)

Follow SRS section 6.3 exactly:
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - Authentication with Sanctum tokens
- `GET /api/recommend?city=...` - Gemini-powered restaurant suggestions
- `GET /api/visited` - User's visit history
- `POST /api/visited` - Mark restaurant as visited
- `DELETE /api/visited/{id}` - Remove from visited list

## Database Schema (Laravel Migrations)

### Users Table
Standard Laravel users with: `id, name, email, password, timestamps`

### VisitedPlaces Table
```php
Schema::create('visited_places', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('place_id'); // Google Places ID
    $table->string('name');
    $table->decimal('lat', 10, 8);
    $table->decimal('lng', 11, 8);
    $table->text('menu_image_url')->nullable();
    $table->json('menu_table')->nullable(); // Structured menu data
    $table->timestamp('visited_at');
    $table->timestamps();
});
```

## Security & Environment Setup

### Backend (.env requirements)
```
GEMINI_API_KEY=your_gemini_key
GOOGLE_MAPS_API_KEY=your_maps_key
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

### Frontend (environment variables)
```
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GOOGLE_MAPS_API_KEY=same_as_backend_key
```

## Testing Strategy Implementation

- **Unit Tests**: Laravel Feature tests for all API endpoints
- **Integration Tests**: User journey flow (register → search → visit → history)
- **PWA Tests**: Lighthouse audits, offline functionality
- **Component Tests**: Vue Test Utils for key components

## Development Workflow Commands

### Backend Setup
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve # Runs on localhost:8000
```

### Frontend Setup
```bash
npm install
npm run dev # Vite dev server on localhost:5173
npm run build # Production PWA build
```

## Common Implementation Gotchas

1. **CORS Configuration**: Ensure Laravel API allows Vue dev server origin
2. **PWA Manifest**: Include proper icons, start_url, and theme_color
3. **Offline Detection**: Use `navigator.onLine` + service worker events
4. **Gemini Rate Limits**: Implement proper error handling and user feedback
5. **Google Maps Quotas**: Cache place details aggressively to minimize API calls

## Key UX Requirements from SRS

- **Mobile-first responsive design** (use Tailwind CSS)
- **<2 second load times** on 4G networks
- **Offline banner** when network unavailable
- **Visit timeline** sorted newest first
- **Alternative recommendations** after marking places visited

When implementing, always refer to SRS sections 4 (Functional Requirements) and 5 (Non-Functional Requirements) for specific behavior expectations.
