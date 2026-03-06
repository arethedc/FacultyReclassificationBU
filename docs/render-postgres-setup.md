# Render + PostgreSQL Setup

## 1. Push repo with `render.yaml`
Render will detect Blueprint config and create:
- Web service: `faculty-reclassification-web`
- Postgres DB: `faculty-reclassification-db`

Note:
- Render may not show native PHP runtime in manual service creation.
- This repo uses Docker runtime (`Dockerfile`) so Laravel works on Render.

## 2. Set required secret env vars (both services)
- `APP_KEY`
- `APP_URL`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`

You can generate app key locally:

```bash
php artisan key:generate --show
```

Copy the full value (example starts with `base64:`) into Render `APP_KEY`.

## 3. Deploy
- First deploy runs:
  - `composer install`
  - `npm run build`
  - `php artisan migrate --force`
  - `php artisan db:seed --class=ProductionBootstrapSeeder --force`
    - seeds only departments + rank levels (no test users)

## 4. Verify
- Open the app URL.
- Create/login user.
- Check email verification flow.
- Scheduler is started inside web service container for free-tier compatibility.
- Check service logs for scheduler activity (`php artisan schedule:work`).
