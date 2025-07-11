# Setup Guide

## Prerequisites
- Node.js & npm
- PHP & Composer
- MySQL

## Backend Setup
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve # localhost:8000
```

## Frontend Setup
```bash
npm install
npm run dev # localhost:5173
npm run build # Production
```

## Environment Variables
### Backend (.env)
```
GEMINI_API_KEY=your_gemini_key
GOOGLE_MAPS_API_KEY=your_maps_key
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```
### Frontend (.env)
```
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GOOGLE_MAPS_API_KEY=same_as_backend_key
```

See [Architecture](Architecture.md) for directory structure.
