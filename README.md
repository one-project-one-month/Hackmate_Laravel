# Hackmate API

## Shared database setup

This API can share one PostgreSQL database with other services (for example `chat`).

Start the DB:

```bash
docker compose up -d db
```

Run Laravel migrations:

```bash
php artisan migrate
```

For full two-repo migration steps (including backup/restore), see:

- `DB_MIGRATION.md`

## Auth setup (Email/Password + GitHub)

### 1. Required `.env` values

Set these values:

- `GITHUB_OAUTH_CLIENT_ID`
- `GITHUB_OAUTH_CLIENT_SECRET`
- `GITHUB_OAUTH_REDIRECT_URI=http://localhost:8000/api/v1/github/callback`
- `FRONTEND_GITHUB_CALLBACK_URL=http://localhost:5173/auth/github/callback`
- `JWT_SECRET`

Generate `JWT_SECRET` (once per environment):

```bash
php artisan jwt:secret
```

### 2. Email/password login

`POST /api/v1/auth/login`

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

Returns JWT token payload (`access_token`, `token_type`, `expires_in`).

### 3. GitHub login flow (SPA/mobile)

1. Call `GET /api/v1/github/login-url`.
2. Redirect browser to returned `url`.
3. GitHub redirects to backend callback.
4. Backend redirects to:
   - `FRONTEND_GITHUB_CALLBACK_URL?token=<jwt_token>`

### 4. Refresh token

`POST /api/v1/auth/refresh` with header:

`Authorization: Bearer <access_token>`

### 5. Current user profile

`GET /api/v1/auth/me` with header:

`Authorization: Bearer <access_token>`
