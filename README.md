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
- `PASSPORT_PASSWORD_CLIENT_ID`
- `PASSPORT_PASSWORD_CLIENT_SECRET`
- `PASSPORT_AUTH_CODE_CLIENT_ID`
- `PASSPORT_AUTH_CODE_CLIENT_SECRET`

### 2. Email/password login

`POST /api/v1/auth/login`

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

Returns Passport token payload (`access_token`, `refresh_token`, `expires_in`).

### 3. GitHub login flow (SPA/mobile)

1. Call `GET /api/v1/github/login-url`.
2. Redirect browser to returned `url`.
3. GitHub redirects to backend callback.
4. Backend redirects to:
   - `FRONTEND_GITHUB_CALLBACK_URL?code=<short_lived_code>`
5. Frontend calls `POST /api/v1/auth/exchange`:

```json
{
  "code": "the_code_from_query_param"
}
```

Response is the same Passport token payload as email/password login.

### 4. Refresh token

`POST /api/v1/auth/refresh`

```json
{
  "refresh_token": "<refresh_token>"
}
```

### 5. Current user profile

`GET /api/v1/auth/me` with header:

`Authorization: Bearer <access_token>`
