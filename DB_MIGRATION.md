# Database (shared Postgres) + migrations

`hackmate-api` and `chat` are intended to share **one** PostgreSQL database in local development.

Both repos’ `docker-compose.yml` use:

- Compose project name: `hackmate`
- Shared volume name: `hackmate_db_data`

So if you start the DB from either repo, you get the same container/data.

## Start/stop the DB

From either repo:

```bash
docker compose up -d db
```

Stop (keeps data):

```bash
docker compose down
```

Stop and delete all data (destructive):

```bash
docker compose down -v
```

## Connection settings

Defaults (see `.env.example`):

- Host: `127.0.0.1`
- Port: `5432` (or `$POSTGRES_PORT`)
- Database: `db` (or `$POSTGRES_DB`)
- Username: `hackmate_user` (or `$POSTGRES_USER`)
- Password: `hackmate_password` (or `$POSTGRES_PASSWORD`)

## Migrations (source of truth = API)

Run schema migrations from this repo:

```bash
php artisan migrate
```

Status:

```bash
php artisan migrate:status
```

Reset schema (destructive):

```bash
php artisan migrate:fresh
```

## Backup/restore (optional)

Backup:

```bash
docker exec -t hackmate-db-1 pg_dump -U hackmate_user db > backup.sql
```

Restore:

```bash
docker exec -i hackmate-db-1 psql -U hackmate_user -d db < backup.sql
```

If your container name differs, run `docker ps` and replace `hackmate-db-1`.
