# Backend Guide

## Technologies
- Laravel
- MySQL
- Sanctum (auth)
- Google Gemini API
- Google Maps/Places API

## Key Controllers
- `AuthController.php`: Registration, login, logout
- `RecommendController.php`: Gemini API integration
- `VisitedController.php`: CRUD for visited places

## Models
- `User.php`
- `VisitedPlace.php`

## Migrations
- Users table (standard)
- VisitedPlaces table (see README)

See [API Reference](API-Reference.md) for endpoints.
