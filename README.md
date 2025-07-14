# EatGemi 🍽️

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/ChandanShakya/eat-gemi)

AI-Powered Restaurant Finder — Vue 3 PWA + Laravel API with Google Gemini AI integration for smart restaurant discovery, menu access, and offline support.

---

## 🚀 Project Overview 

EatGemi is a modern, mobile-first PWA for discovering restaurants using AI. It combines Vue 3, Vite, Laravel, Google Gemini AI, and Google Maps/Places APIs to deliver smart recommendations, menu previews, and visit tracking — all with offline capabilities.

### Key Features
- **AI Recommendations**: Google Gemini API suggests restaurants based on city and user preferences.
- **Google Maps Integration**: Interactive map with pins, info windows, and offline fallback.
- **Menu Access**: Structured menu tables or images from Places API/Gemini.
- **Visit Tracking**: Mark places as visited, view timeline, and get alternative suggestions.
- **Offline Support**: PWA with service worker caching and offline indicator.
- **Authentication**: Secure login/register via Laravel Sanctum.
- **Mobile-First UI**: Responsive design with Tailwind CSS.

---

## 🏗️ Architecture

**Frontend:** Vue 3 + Vite PWA
**Backend:** Laravel REST API (MySQL)
**AI:** Google Gemini API (via backend)
**Maps:** Google Maps & Places APIs
**State:** Pinia (auth, restaurants, visited)
**Offline:** Service worker, Workbox, PWA manifest

### Directory Structure
```
├── backend/                    # Laravel API
│   ├── app/Http/Controllers/
│   │   ├── AuthController.php
│   │   ├── RecommendController.php
│   │   └── VisitedController.php
│   ├── app/Models/
│   │   ├── User.php
│   │   └── VisitedPlace.php
│   └── routes/api.php
├── frontend/                   # Vue 3 PWA
│   ├── src/components/
│   │   ├── MapView.vue
│   │   ├── RestaurantCard.vue
│   │   └── VisitedList.vue
│   ├── src/views/
│   │   ├── HomeView.vue
│   │   └── AuthView.vue
│   ├── src/stores/
│   │   ├── auth.js
│   │   └── restaurants.js
│   ├── public/manifest.json
│   └── vite.config.js
```

---

## 🔗 API Endpoints

| Endpoint                | Method | Description                                 |
|-------------------------|--------|---------------------------------------------|
| /api/auth/register      | POST   | User registration                           |
| /api/auth/login         | POST   | Login, returns Sanctum token                |
| /api/recommend?city=... | GET    | Gemini-powered restaurant suggestions       |
| /api/visited            | GET    | User's visit history                        |
| /api/visited            | POST   | Mark restaurant as visited                  |
| /api/visited/{id}       | DELETE | Remove from visited list                    |

---

## 🗄️ Database Schema

### Users Table
Standard Laravel users: `id, name, email, password, timestamps`

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

---

## ⚡ Setup Instructions

### Prerequisites
- Node.js 18+ and npm
- PHP 8.2+ and Composer
- MySQL 8.0+
- Google Cloud Platform account (for Gemini & Maps API keys)

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Configure database and API keys in .env
php artisan migrate
php artisan serve # Runs on localhost:8000
```

### Frontend (Vue 3 + Vite)
```bash
cd frontend
npm install

# Create .env with API configuration
npm run dev # Vite dev server on localhost:5173
npm run build # Production PWA build
```

### Environment Variables

**Backend (.env):**
```env
APP_NAME=EatGemi
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_DATABASE=eatgemi
DB_USERNAME=your_username
DB_PASSWORD=your_password

GEMINI_API_KEY=your_gemini_api_key_here
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

**Frontend (.env):**
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
```

### Getting API Keys

1. **Google Gemini API**: Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. **Google Maps API**: Visit [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
   - Enable: Maps JavaScript API, Places API, Geocoding API

### Production Deployment

**Backend (Laravel):**
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Frontend (PWA):**
```bash
npm run build
# Deploy dist/ folder to static hosting (Netlify, Vercel, etc.)
```

---

## 🧑‍💻 Development Commands

**Backend:**
```bash
# Install dependencies
composer install

# Run tests
php artisan test

# Start development server
php artisan serve

# Database operations
php artisan migrate
php artisan migrate:fresh --seed

# Code formatting
./vendor/bin/pint

# Clear caches
php artisan optimize:clear
```

**Frontend:**
```bash
# Install dependencies
npm install

# Development server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Run tests
npm run test

# Type checking
npm run type-check

# Lint and format code
npm run lint
npm run format

# PWA analysis
npm run build:analyze
```

---

## 🧪 Testing Strategy

- **Unit Tests**: Laravel Feature tests for API endpoints
- **Integration Tests**: User journey (register → search → visit → history)
- **PWA Tests**: Lighthouse audits, offline functionality
- **Component Tests**: Vue Test Utils for key components

---

## 🛡️ Security

- **CORS**: Laravel API must allow Vue dev server origin
- **API Keys**: Never exposed to frontend
- **Sanctum**: Token-based authentication

---

## 🎨 UX Requirements

- Mobile-first responsive design (Tailwind CSS)
- <2s load times on 4G
- Offline banner when network unavailable
- Visit timeline sorted newest first
- Alternative recommendations after marking visited

---

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 📄 License

MIT — see [LICENSE](LICENSE)

---

## 📚 Wiki

See [EatGemi Wiki](https://github.com/ChandanShakya/eat-gemi/wiki) for detailed documentation, architecture, API usage, troubleshooting, and more.
