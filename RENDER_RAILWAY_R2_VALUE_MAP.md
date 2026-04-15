# Render + Railway + R2 Value Map

This file is the concrete reference for the recommended production stack:

- `Render` for the Laravel web app and the private FastAPI AI service
- `Railway MySQL` for the database
- `Cloudflare R2` for evidence, selfie, and profile photo storage

Use this together with:

- `render.yaml`
- `DEPLOYMENT_RENDER_RAILWAY.md`
- `GITHUB_RENDER_IMPORT_CHECKLIST.md`

## Public domain

Use one public host for the website, PWA, and websocket client.

Examples:

- default Render domain:
  - `https://stitch-web.onrender.com`
- custom domain:
  - `https://rescue.example.com`

Important rule:

- `APP_URL` and `REVERB_HOST` must point to the same public host

## Render env values to fill

### Core app

- `APP_URL`
  - example:
    - `https://stitch-web.onrender.com`
- `APP_KEY`
  - generate from:
    - `php artisan key:generate --show`

### Railway MySQL -> Laravel DB mapping

Map Railway values like this:

- Railway `MYSQLHOST` -> Render `DB_HOST`
- Railway `MYSQLPORT` -> Render `DB_PORT`
- Railway `MYSQLDATABASE` -> Render `DB_DATABASE`
- Railway `MYSQLUSER` -> Render `DB_USERNAME`
- Railway `MYSQLPASSWORD` -> Render `DB_PASSWORD`

If Railway gives `MYSQL_URL`, keep it as reference only. This Laravel setup uses the split DB fields above.

### Cloudflare R2 -> Laravel S3 mapping

Map R2 values like this:

- R2 `Access Key ID` -> Render `AWS_ACCESS_KEY_ID`
- R2 `Secret Access Key` -> Render `AWS_SECRET_ACCESS_KEY`
- R2 bucket name -> Render `AWS_BUCKET`
- R2 endpoint -> Render `AWS_ENDPOINT`

Keep:

- `AWS_DEFAULT_REGION=auto`
- `AWS_USE_PATH_STYLE_ENDPOINT=false`

Example:

- `AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com`

Optional:

- `AWS_URL`
  - leave blank at first unless you configure a public bucket hostname

### Reverb / realtime

Use your same public web host:

- `REVERB_HOST=stitch-web.onrender.com`
  - or your custom domain

Generate and set:

- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`

Keep:

- `REVERB_APP_ID=stitch-reverb-app`
- `REVERB_PORT=443`
- `REVERB_SCHEME=https`
- `REVERB_SERVER_HOST=127.0.0.1`
- `REVERB_SERVER_PORT=8080`

### AI service

Do not set a public AI URL manually.

The Render blueprint already links:

- `AI_SEVERITY_SERVICE_HOST` from the private `stitch-ai` service
- `AI_SEVERITY_SERVICE_PORT` from the private `stitch-ai` service

## Recommended first-production values

For the first safe deployment:

1. use the default Render domain
2. use Railway MySQL
3. use one Cloudflare R2 bucket
4. keep AI private on Render

## Required secrets

- `APP_URL`
- `APP_KEY`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_BUCKET`
- `AWS_ENDPOINT`
- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`
- `REVERB_HOST`

## Values that can stay as-is

- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql`
- `DB_PORT=3306`
- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `FILESYSTEM_DISK=s3`
- `AWS_DEFAULT_REGION=auto`
- `AWS_USE_PATH_STYLE_ENDPOINT=false`
- `AI_SEVERITY_ENABLED=true`
- `AI_SEVERITY_TIMEOUT=20`
- `AI_SEVERITY_DISPATCH=sync`
- `AI_SEVERITY_MODEL_NAME=bontoc_southern_leyte_production_candidate_external`
- `AI_SEVERITY_MODEL_VERSION=0.2.0`
- `BROADCAST_CONNECTION=reverb`
- `REVERB_APP_ID=stitch-reverb-app`
- `REVERB_PORT=443`
- `REVERB_SCHEME=https`
- `REVERB_SERVER_HOST=127.0.0.1`
- `REVERB_SERVER_PORT=8080`
