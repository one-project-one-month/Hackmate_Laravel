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
