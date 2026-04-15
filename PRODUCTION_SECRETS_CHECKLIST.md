# Production Secrets Checklist

Fill this out before the first Render deployment.

Recommended stack:

- Render
- Railway MySQL
- Cloudflare R2

## Public app domain

- `APP_URL=` __________________________________________
- `REVERB_HOST=` ______________________________________

Important:

- these two must point to the same public host

## Laravel app secret

- `APP_KEY=` __________________________________________

Generate from:

```bash
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch\web_system"
php artisan key:generate --show
```

## Railway MySQL

- Railway `MYSQLHOST` -> `DB_HOST=` ____________________
- Railway `MYSQLPORT` -> `DB_PORT=` ____________________
- Railway `MYSQLDATABASE` -> `DB_DATABASE=` ____________
- Railway `MYSQLUSER` -> `DB_USERNAME=` ________________
- Railway `MYSQLPASSWORD` -> `DB_PASSWORD=` ____________

## Cloudflare R2

- `AWS_ACCESS_KEY_ID=` _________________________________
- `AWS_SECRET_ACCESS_KEY=` _____________________________
- `AWS_BUCKET=` _______________________________________
- `AWS_ENDPOINT=` _____________________________________
- `AWS_URL=` __________________________________________

Keep:

- `AWS_DEFAULT_REGION=auto`
- `AWS_USE_PATH_STYLE_ENDPOINT=false`

## Reverb

- `REVERB_APP_KEY=` ___________________________________
- `REVERB_APP_SECRET=` ________________________________
- `REVERB_APP_ID=stitch-reverb-app`
- `REVERB_PORT=443`
- `REVERB_SCHEME=https`
- `REVERB_SERVER_HOST=127.0.0.1`
- `REVERB_SERVER_PORT=8080`

## AI service

These are linked by the Render blueprint:

- `AI_SEVERITY_SERVICE_HOST`
- `AI_SEVERITY_SERVICE_PORT`

Keep:

- `AI_SEVERITY_ENABLED=true`
- `AI_SEVERITY_TIMEOUT=20`
- `AI_SEVERITY_DISPATCH=sync`
- `AI_SEVERITY_MODEL_NAME=bontoc_southern_leyte_production_candidate_external`
- `AI_SEVERITY_MODEL_VERSION=0.2.0`

## Stable defaults for first production deploy

Keep:

- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `FILESYSTEM_DISK=s3`

## Final pre-deploy check

Before importing the Render blueprint, confirm you already have:

- [ ] `APP_URL`
- [ ] `REVERB_HOST`
- [ ] `APP_KEY`
- [ ] `DB_HOST`
- [ ] `DB_PORT`
- [ ] `DB_DATABASE`
- [ ] `DB_USERNAME`
- [ ] `DB_PASSWORD`
- [ ] `AWS_ACCESS_KEY_ID`
- [ ] `AWS_SECRET_ACCESS_KEY`
- [ ] `AWS_BUCKET`
- [ ] `AWS_ENDPOINT`
- [ ] `REVERB_APP_KEY`
- [ ] `REVERB_APP_SECRET`
