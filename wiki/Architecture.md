# Architecture

## Overview
EatGemi is structured as a modern SPA (Vue 3 + Vite) with a Laravel REST API backend. It integrates Google Gemini AI for recommendations and Google Maps/Places APIs for location and menu data.

### Components
- **Frontend**: Vue 3 SPA, Pinia state, Tailwind CSS, PWA (vite-plugin-pwa)
- **Backend**: Laravel API, MySQL, Sanctum auth, Gemini/Maps API integration
- **AI**: Gemini API (via backend)
- **Maps**: Google Maps JS API, Places API
- **Offline**: Service worker, caching, offline indicator

### Data Flow
1. User searches for restaurants (city input)
2. Backend calls Gemini API for suggestions
3. Frontend displays results, fetches details from Places API
4. User marks places as visited; data stored in backend and cached for offline
5. Alternative recommendations provided post-visit

See [API Reference](API-Reference.md) and [Setup Guide](Setup-Guide.md) for more.
