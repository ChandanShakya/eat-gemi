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

### Backend (Laravel)
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve # Runs on localhost:8000
```

### Frontend (Vue 3 + Vite)
```bash
npm install
npm run dev # Vite dev server on localhost:5173
npm run build # Production PWA build
```

### Environment Variables

**Backend (.env):**
```
GEMINI_API_KEY=your_gemini_key
GOOGLE_MAPS_API_KEY=your_maps_key
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

**Frontend (.env):**
```
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GOOGLE_MAPS_API_KEY=same_as_backend_key
```

---

## 🧑‍💻 Development Patterns

- **Gemini API calls**: Always via backend, never expose keys to frontend
- **Error handling**: Exponential backoff for rate limits
- **Prompt structure**: "Suggest 5 restaurants in <<city>> with menu table or image links"
- **Pinia state**: Auth, restaurants, visited, offline indicator
- **PWA**: vite-plugin-pwa, Workbox, offline banner, install prompt
- **Google Maps**: JS API, pins, info windows, offline fallback

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

See [EatGemi Wiki](./wiki/Home.md) for detailed documentation, architecture, API usage, troubleshooting, and more.
