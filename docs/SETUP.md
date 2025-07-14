# EatGemi Setup Instructions

Complete setup guide for the EatGemi AI Restaurant Finder project.

## System Requirements

- **Node.js**: 18.0+ (LTS recommended)
- **PHP**: 8.2+ with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- **Composer**: 2.0+
- **MySQL**: 8.0+
- **Google Cloud Platform**: Account with billing enabled

## Project Setup

### 1. Clone Repository

```bash
git clone https://github.com/YourUsername/eat-gemi.git
cd eat-gemi
```

### 2. Backend Setup (Laravel)

```bash
cd backend

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### Configure Environment (.env)

```env
APP_NAME=EatGemi
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eatgemi
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password

# Google API Keys
GEMINI_API_KEY=your_gemini_api_key_here
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here

# CORS Configuration
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

#### Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE eatgemi;
EXIT;

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

Backend will be available at: `http://localhost:8000`

### 3. Frontend Setup (Vue 3 + Vite)

```bash
cd frontend

# Install Node.js dependencies
npm install
```

#### Configure Environment (.env)

```env
# API Configuration
VITE_API_BASE_URL=http://localhost:8000/api

# Google Maps API Key (same as backend)
VITE_GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
```

#### Start Development Server

```bash
npm run dev
```

Frontend will be available at: `http://localhost:5173`

## Google API Setup

### 1. Google Gemini API

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create or select a project
3. Generate an API key
4. Copy the key to your `.env` files

### 2. Google Maps & Places API

1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create or select a project
3. Enable required APIs:
   - Maps JavaScript API
   - Places API
   - Geocoding API
4. Create credentials (API Key)
5. Restrict the API key (optional but recommended):
   - HTTP referrers: `localhost:*`, your production domain
   - API restrictions: Select the enabled APIs

## Development Workflow

### Running Both Servers

Terminal 1 (Backend):
```bash
cd backend
php artisan serve
```

Terminal 2 (Frontend):
```bash
cd frontend
npm run dev
```

### Testing the Application

1. Open `http://localhost:5173` in your browser
2. Register a new account
3. Login with your credentials
4. Search for restaurants in a city (e.g., "New York", "Tokyo")
5. View restaurants on the map
6. Mark restaurants as visited
7. Check your visit history

## Production Deployment

### Backend (Laravel)

```bash
cd backend

# Install production dependencies
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set production environment
APP_ENV=production
APP_DEBUG=false
```

### Frontend (PWA)

```bash
cd frontend

# Build for production
npm run build

# Files will be in dist/ folder
# Deploy to static hosting (Netlify, Vercel, CloudFront, etc.)
```

## Environment Variables Reference

### Backend Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Laravel application key | `base64:...` |
| `DB_*` | Database configuration | See above |
| `GEMINI_API_KEY` | Google Gemini API key | `AI...` |
| `GOOGLE_MAPS_API_KEY` | Google Maps API key | `AIza...` |
| `SANCTUM_STATEFUL_DOMAINS` | CORS domains | `localhost:5173` |

### Frontend Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `VITE_API_BASE_URL` | Backend API URL | `http://localhost:8000/api` |
| `VITE_GOOGLE_MAPS_API_KEY` | Google Maps API key | `AIza...` |

## Troubleshooting

### Common Issues

1. **CORS Errors**
   - Ensure `SANCTUM_STATEFUL_DOMAINS` includes your frontend URL
   - Check `config/cors.php` configuration

2. **Database Connection**
   - Verify MySQL is running
   - Check database credentials in `.env`
   - Ensure database exists

3. **API Key Issues**
   - Verify API keys are correct
   - Check API quotas in Google Cloud Console
   - Ensure required APIs are enabled

4. **PWA Installation**
   - Serve over HTTPS in production
   - Check manifest.json is accessible
   - Verify service worker registration

### Development Commands

**Backend:**
```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list

# Database fresh start
php artisan migrate:fresh

# Run tests
php artisan test
```

**Frontend:**
```bash
# Install dependencies
npm install

# Check for updates
npm audit

# Type checking (if using TypeScript)
npm run type-check

# Lint code
npm run lint
```

## Next Steps

After setup completion:

1. Read the [Software Requirements Specification](../docs/eat-gemi-srs.md)
2. Review [Contributing Guidelines](../CONTRIBUTING.md)
3. Check the [Project Management Guide](../PROJECT_MANAGEMENT.md)
4. Explore the API documentation at `http://localhost:8000/api/documentation`

## Support

For issues:
1. Check this setup guide
2. Review existing [GitHub Issues](../../issues)
3. Create a new issue with detailed description

---

**Happy coding! 🍽️✨**
