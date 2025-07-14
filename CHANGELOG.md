# Changelog

All notable changes to the EatGemi project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project setup and foundation
- Laravel 12 backend with REST API
- Vue 3 frontend with Vite and PWA capabilities
- Google Gemini AI integration for restaurant recommendations
- Google Maps and Places API integration
- User authentication with Laravel Sanctum
- Restaurant visit tracking and history
- Offline support with service worker caching
- Mobile-first responsive design with Tailwind CSS
- Progressive Web App (PWA) features
- State management with Pinia
- Database schema for users and visited places
- API endpoints for auth, recommendations, and visits
- CORS configuration for frontend-backend communication
- Development and production build configurations
- Comprehensive documentation and setup guides

### Features
- ✅ User registration and authentication
- ✅ AI-powered restaurant recommendations by city
- ✅ Interactive map with restaurant pins
- ✅ Menu viewing (images and structured tables)
- ✅ Visit tracking and timeline
- ✅ Offline functionality with caching
- ✅ PWA installation prompts
- ✅ Responsive mobile-first design
- ✅ Real-time network status monitoring
- ✅ Alternative recommendations after visits

### API Endpoints
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User authentication
- `POST /api/auth/logout` - User logout
- `GET /api/recommend` - Get restaurant recommendations
- `GET /api/visited` - Get user's visited places
- `POST /api/visited` - Mark restaurant as visited
- `DELETE /api/visited/{id}` - Remove from visited list

### Components
- RestaurantCard - Individual restaurant display
- MapView - Google Maps integration with pins
- VisitedList - User's visit history management
- AuthView - Login and registration forms
- HomeView - Main application interface

### Technical Specifications
- Backend: Laravel 12, MySQL, Sanctum
- Frontend: Vue 3, Vite, Tailwind CSS, Pinia
- APIs: Google Gemini, Google Maps, Google Places
- PWA: Service worker, manifest, offline support
- Authentication: Token-based with Laravel Sanctum
- State Management: Pinia stores for auth and restaurants

## [0.1.0] - 2024-01-XX

### Added
- Initial project structure and foundational setup
- Complete backend and frontend scaffolding
- All core components and views
- Database migrations and models
- API controllers and routes
- PWA configuration and manifest
- Development environment setup
- Documentation and setup guides

---

## Contributing

When adding entries to this changelog:

1. Follow the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format
2. Group changes into: Added, Changed, Deprecated, Removed, Fixed, Security
3. Include issue/PR references where applicable
4. Update the [Unreleased] section for ongoing work
5. Create version tags when releasing

---

For more details about changes, see the [commit history](../../commits/main) and [releases](../../releases).
