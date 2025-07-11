# 📘 Software Requirements Specification (SRS)

**Project:** AI‑Powered Restaurant Finder SPA + PWA  
**Version:** 1.0  
**Author:** [Your Name]  
**Date:** July 11, 2025  

---

## 1. Introduction

### 1.1 Purpose  
This document specifies the functional and non-functional requirements for a Progressive Web App that allows users to search for restaurants, view up-to-date menus, mark visited places, and access intelligent suggestions via AI—all within an SPA interface.

### 1.2 Scope  
- SPA built with **Vue 3 + Vite**  
- **PWA** support (installable, offline capable)  
- **Laravel (PHP)** backend serving API endpoints  
- AI recommendations using **Google Gemini API**  
- Restaurant data via **Google Maps & Places APIs**  
- User authentication & history tracking  
- Real-time menu images or structured menus

### 1.3 Definitions, Acronyms, Abbreviations  
- **SPA**: Single Page Application  
- **PWA**: Progressive Web App  
- **API**: Application Programming Interface  
- **AI**: Artificial Intelligence  
- **UX**: User Experience

---

## 2. System Overview

- **Users**: Guest, Registered, Admin  
- **Frontend**: Vue 3 PWA  
- **Backend**: Laravel REST API  
- **APIs**: Gemini, Maps, Places  
- **Data Storage**: MySQL (Laravel), IndexedDB (frontend caching)

---

## 3. User Needs & Personas

### 3.1 Guest User  
- Wants to browse restaurant suggestions  
- Can view menus and pin locations  
- Must register to save visited list

### 3.2 Registered User  
- Can login/logout  
- Receive personalized suggestions  
- Mark and view history of visited places  
- Use app offline as PWA

### 3.3 Admin  
- Manage user data (optional)  
- Monitor API usage and system health

---

## 4. Functional Requirements

| ID  | Requirement                                                                                                                                 |
|-----|---------------------------------------------------------------------------------------------------------------------------------------------|
| FR1 | User can register via email/password (optionally, social login).                                                                            |
| FR2 | User can login/logout; token-based authentication (Laravel Sanctum or JWT).                                                               |
| FR3 | System fetches restaurant suggestions using Gemini API based on location input.                                                            |
| FR4 | System displays restaurant pins on Google Maps with click-to-view info window.                                                             |
| FR5 | Clicking logo displays restaurant menu: structured table (if text-based) or menu image.                                                    |
| FR6 | Users can mark a restaurant as visited via UI; stored in DB and front-end store.                                                           |
| FR7 | Users can view their visit history with timestamps, sorted newest first.                                                                   |
| FR8 | After marking visited, AI provides 2–3 alternative suggestions as add-ons via "alternative recommendations."                               |
| FR9 | App works offline: previously visited restaurants and menus are accessible; map minimal functionality available.                          |
| FR10| App is installable (PWA) with manifest and service worker.                                                                                 |
| FR11| Admin Dashboard: view API usage stats (optional).                                                                                          |

---

## 5. Non‑Functional Requirements

| ID    | Requirement                                                                                                 |
|-------|-------------------------------------------------------------------------------------------------------------|
| NFR1  | UI must be responsive (mobile-first) and use Tailwind CSS or similar.                                      |
| NFR2  | 95% of main pages load in under 2 seconds on 4G.                                                             |
| NFR3  | The system shall operate offline for core features.                                                         |
| NFR4  | Data must be secured against unauthorized access (HTTPS, sanitized inputs).                                 |
| NFR5  | Gemini & Google API keys stored securely on server only.                                                    |
| NFR6  | App passes PWA audits (Lighthouse score ≥90 for PWA, Accessibility).                                        |
| NFR7  | Backend handles at least 100 concurrent API requests.                                                       |
| NFR8  | Logs generated for API errors; retention at least 30 days.                                                  |

---

## 6. External Interfaces

### 6.1 Gemini API (Google Generative Language)  
- Endpoint: `/generateContent`  
- Prompt example:  
  ```text
  Suggest 5 restaurants in <<city>> with menu table or image links.
```

* Output: JSON with recommendations and optional menu data.

### 6.2 Google Maps & Places API

* Map rendering (JS API)
* Place search + place details (fields: name, geometry, photos, website)
* Fallback for menu images

### 6.3 Laravel REST API

* `POST /api/auth/register`
* `POST /api/auth/login`
* `GET /api/recommend?city=...`
* `GET /api/visited`
* `POST /api/visited`
* `DELETE /api/visited/{id}`

---

## 7. Data Models

### 7.1 User

* `id, name, email, password_hash, created_at, updated_at`

### 7.2 VisitedPlace

* `id, user_id, place_id, name, lat, lng, menu_image_url, menu_table JSON, visited_at, created_at`

---

## 8. UI / UX Screens

1. **Landing & Search**: Search bar + map + recommended restaurants panel
2. **Login/Register**: Basic form + validation
3. **Map InfoWindow**: Shows name, menu preview, visited marker
4. **Visited List**: Timeline or tabular list
5. **Offline Indicator**: Banner when offline
6. **PWA Install Prompt**

---

## 9. Workflow Diagrams

### 9.1 User Journey

```
[User visits site] → [Login/Register?] → [Search](city) → [AI recommends restaurants] 
→ [Map loads places] → [Click place → Show menu] → [Mark visited] → [View visited list]
```

### 9.2 Offline Usage

```
[First use online: data cached]  
→ [Later offline: show cached places & menus only]
```

---

## 10. Error Handling

* AI failure → Show message "Try again later"
* API rate limit exceeded → Exponential backoff + user notification
* Offline / no map → "Offline mode active" banner
* Auth failure → Redirect to login

---

## 11. Security and Privacy

* HTTPS everywhere
* CSRF/XSS protection
* Secure storage of tokens
* GDPR compliance (user data deletion endpoints)

---

## 12. PWA Features

* Manifest: name, icons, theme\_color, start\_url
* Workbox caching: static assets, visited data, Gemini responses
* Service worker updates automatically

---

## 13. Testing Plan

* Unit tests: Laravel endpoints, Vue components
* Integration tests: user signup/search/visit cycle
* PWA tests: offline-enabled, installable
* Performance tests: Lighthouse + backend stress tests

---

## 14. Project Setup Instructions

### Backend Setup

```bash
git clone <repo>
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install (if using Mix)
php artisan serve
```

### Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

---

## 15. GitHub Copilot Integration Tips

* **Create `.mpx/` or scaffolding:** Use this markdown file as README.md
* **Use Copilot to scaffold controllers/components** based on the endpoints and requirements above
* **Define schema via `php artisan make:model` and migrations** per section 7
* **Use Vue CLI & PWA plugin scaffold** per section 13
* **Set up service worker via `vite-plugin-pwa`**

---

## 16. Future Enhancements

* Social login (Google/Facebook)
* Restaurant ratings & user reviews
* AI-powered personalized recommendations
* Voice search & language support
* Export visited history (CSV/PDF)
