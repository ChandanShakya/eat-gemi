# Frontend Guide

## Technologies
- Vue 3 + Vite
- Pinia (state management)
- Tailwind CSS
- PWA (vite-plugin-pwa)
- Google Maps JS API

## Key Components
- `MapView.vue`: Google Maps integration, pins, info windows, offline fallback
- `RestaurantCard.vue`: Restaurant display, menu preview
- `VisitedList.vue`: User's visit history
- `HomeView.vue`: Search interface
- `AuthView.vue`: Login/register forms

## State Management
- Pinia stores: `auth.js`, `restaurants.js`
- Offline indicator via `navigator.onLine` and service worker events

See [Setup Guide](Setup-Guide.md) for installation.
