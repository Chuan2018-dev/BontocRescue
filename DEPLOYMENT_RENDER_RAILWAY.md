# Online Deployment Plan

This repository is best deployed as:

1. `Render web service` for Laravel + PWA + Reverb
2. `Render private service` for the FastAPI AI server
3. `Railway MySQL` for the production database
4. `S3-compatible object storage` for evidence uploads

Current GitHub repository:

- `https://github.com/Chuan2018-dev/BontocRescue`

This is the recommended layout for the current codebase because the project already depends on:

- Laravel web + auth + uploads
- Laravel Reverb for realtime updates
- FastAPI + PyTorch for AI inference
- MySQL schemas and migrations

## Recommended Architecture

### Public

- `stitch-web` on Render
  - serves the Laravel web app and installable PWA
  - terminates HTTP/HTTPS
  - proxies websocket traffic to the local Reverb process inside the same container

### Private

- `stitch-ai` on Render private networking
  - serves `FastAPI /health` and `FastAPI /predict`
  - only Laravel needs to call this service

### External managed services

- `Railway MySQL`
  - stores application data
  - easiest fit for the existing Laravel MySQL schema

- `S3-compatible object storage`
  - stores incident evidence, videos, selfies, and profile photos
  - avoids losing files during container restarts or redeploys

## Why this is the recommended path

- Keeps the current Laravel + Reverb + FastAPI design
- Avoids moving the project to Postgres just for hosting convenience
- Avoids storing uploads on ephemeral container disks
- Keeps AI private instead of exposing it directly to the public internet
- Minimizes code changes versus the current local Laragon setup

## Files added for this plan

- Root Render blueprint: `render.yaml`
- Concrete value map:
  - `RENDER_RAILWAY_R2_VALUE_MAP.md`
- Production secrets worksheet:
  - `PRODUCTION_SECRETS_CHECKLIST.md`
- GitHub + Render import checklist:
  - `GITHUB_RENDER_IMPORT_CHECKLIST.md`
- GitHub first-push commands:
  - `GITHUB_FIRST_PUSH_COMMANDS.md`
- Web service Docker image:
  - `web_system/deploy/render/Dockerfile`
  - `web_system/deploy/render/start-web.sh`
  - `web_system/deploy/render/php.ini`
  - `web_system/deploy/render/nginx.default.conf.template`
- AI service Docker image:
  - `ai_service/deploy/render/Dockerfile`
- Example env files:
  - `web_system/.env.render.example`
  - `ai_service/.env.render.example`

## Step-by-step

### 1. Push the repository to GitHub

Render and Railway are easiest to connect from a GitHub repo.

This repo is already connected. Use:

```bash
git push origin main
```

### 2. Create the MySQL database

Recommended first option: `Railway MySQL`

Create a MySQL database in Railway and collect:

- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### 3. Create object storage for uploads

Use an S3-compatible bucket.

Recommended options:

- `Cloudflare R2` for low-cost object storage
- `AWS S3` if you want the most standard setup

Collect:

- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`
- `AWS_ENDPOINT` if using a non-AWS provider
- `AWS_URL` if you want a direct public asset base URL

### 4. Generate the Laravel app key

From `web_system/`:

```bash
php artisan key:generate --show
```

Save the generated `base64:...` value for `APP_KEY`.

### 5. Import the Render blueprint

At Render:

1. Create a new Blueprint
2. Point it to this repository
3. Render will detect the root `render.yaml`

This creates:

- `stitch-web`
- `stitch-ai`

### 6. Fill the required Render secrets

Render will prompt for all `sync: false` values from `render.yaml`.

Minimum required values:

- `APP_URL`
- `APP_KEY`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`
- `AWS_DEFAULT_REGION`
- `AWS_BUCKET`
- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`
- `REVERB_HOST`

For the exact Render + Railway + R2 value mapping, use:

- `RENDER_RAILWAY_R2_VALUE_MAP.md`

For a fill-in-the-blanks production secret worksheet, use:

- `PRODUCTION_SECRETS_CHECKLIST.md`

For the exact GitHub push and Render import sequence, use:

- `GITHUB_RENDER_IMPORT_CHECKLIST.md`

If the folder is not yet a Git repo, use:

- `GITHUB_FIRST_PUSH_COMMANDS.md`

### 7. Set the correct public domain values

If using the default Render domain first:

- `APP_URL=https://your-stitch-web-service.onrender.com`
- `REVERB_HOST=your-stitch-web-service.onrender.com`

If using a custom domain:

- `APP_URL=https://your-domain.com`
- `REVERB_HOST=your-domain.com`

Use:

- `REVERB_PORT=443`
- `REVERB_SCHEME=https`

### 8. Deploy

The web container startup script will:

- render the nginx config with the assigned Render `PORT`
- run migrations
- ensure the storage link exists
- start `php-fpm`
- start `Laravel Reverb`
- start `nginx`

### 9. Verify production

Check:

- `GET /system/version`
- login
- registration
- civilian report creation
- evidence upload
- responder live feed
- AI health from Laravel to FastAPI

## Runtime choices in this setup

### Web service

- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=sync`
- `FILESYSTEM_DISK=s3`

These are deliberate:

- database sessions survive container restarts better than file sessions
- sync queue keeps the first deployment simpler
- S3 storage prevents evidence loss on redeploys

## Database recommendation

### Best default choice: Railway MySQL

Use this if you want:

- MySQL without changing the current Laravel schema
- simpler setup for the existing repo
- less migration work

### Alternative: Aiven MySQL

Use this if you want:

- a more managed standalone MySQL service
- backups and a stable hosted MySQL product

## Storage recommendation

### Best practical choice

- `Cloudflare R2` if you want cheaper S3-compatible storage
- `AWS S3` if you want the most standard Laravel-compatible path

Do not keep uploads on the app container filesystem in production.

## Notes about Vercel

Vercel is not the best host for the full current stack because this repo depends on:

- Laravel PHP runtime
- Reverb websocket server
- FastAPI AI service
- uploaded media files

If you later want to use Vercel, the cleaner split would be:

- Vercel for a separate frontend
- Render or Railway for Laravel API + Reverb + AI
- external DB + object storage

## First future improvement after production

After the first stable deployment, the next upgrade should be:

1. move AI dispatch to `QUEUE_CONNECTION=database`
2. add a Render worker for queued AI jobs
3. optionally replace self-hosted Reverb with a managed realtime provider if you want simpler operations
