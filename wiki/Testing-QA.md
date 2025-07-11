# Testing & QA

## Testing Strategy
- **Unit Tests**: Laravel Feature tests for API endpoints
- **Integration Tests**: User journey (register → search → visit → history)
- **PWA Tests**: Lighthouse audits, offline functionality
- **Component Tests**: Vue Test Utils for key components

## Running Tests
- Backend: `php artisan test`
- Frontend: `npm run test` (if configured)

## QA Checklist
- API endpoints covered
- Offline functionality verified
- UI responsive and mobile-first
- Security checks (auth, CORS)

See [Troubleshooting](Troubleshooting.md) for common issues.
