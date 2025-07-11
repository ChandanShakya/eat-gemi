# API Reference

## Endpoints
| Endpoint                | Method | Description                                 |
|-------------------------|--------|---------------------------------------------|
| /api/auth/register      | POST   | User registration                           |
| /api/auth/login         | POST   | Login, returns Sanctum token                |
| /api/recommend?city=... | GET    | Gemini-powered restaurant suggestions       |
| /api/visited            | GET    | User's visit history                        |
| /api/visited            | POST   | Mark restaurant as visited                  |
| /api/visited/{id}       | DELETE | Remove from visited list                    |

## Auth
- Uses Laravel Sanctum for token-based authentication.

## Error Handling
- Exponential backoff for Gemini rate limits
- CORS enabled for frontend origin

See [Backend Guide](Backend-Guide.md) for controller details.
