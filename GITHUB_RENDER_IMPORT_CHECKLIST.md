# GitHub Push + Render Import Checklist

Use this checklist in order for the current recommended stack:

- Render web service
- Render private AI service
- Railway MySQL
- Cloudflare R2

## 1. Prepare the repo locally

From the project root:

```bash
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
```

Confirm these files exist:

- `render.yaml`
- `DEPLOYMENT_RENDER_RAILWAY.md`
- `RENDER_RAILWAY_R2_VALUE_MAP.md`
- `web_system/deploy/render/Dockerfile`
- `ai_service/deploy/render/Dockerfile`

## 2. Push to GitHub

This repo is already connected to:

- `https://github.com/Chuan2018-dev/BontocRescue`

From the project root:

```bash
git remote -v
git status
git push origin main
```

If you make more changes later:

```bash
git add .
git commit -m "Update deployment config"
git push origin main
```

## 3. Create Railway MySQL

At Railway:

1. Create a project
2. Add `MySQL`
3. Open the service variables
4. Copy:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

## 4. Create Cloudflare R2 bucket

At Cloudflare:

1. Create one bucket
2. Create access keys
3. Copy:
   - `Access Key ID`
   - `Secret Access Key`
   - bucket name
   - account endpoint

## 5. Generate APP_KEY

From `web_system/`:

```bash
php artisan key:generate --show
```

Copy the generated `base64:...` value.

## 6. Import into Render

At Render:

1. Click `New`
2. Choose `Blueprint`
3. Connect GitHub if needed
4. Select the repository:
   - `Chuan2018-dev/BontocRescue`
5. Render will detect `render.yaml`

Expected services:

- `stitch-web`
- `stitch-ai`

## 7. Fill the Render env values

Use:

- `RENDER_RAILWAY_R2_VALUE_MAP.md`

Set these first:

### App

- `APP_URL=https://stitch-web.onrender.com`
- `APP_KEY=<generated base64 key>`

### Database

- `DB_HOST=<MYSQLHOST>`
- `DB_PORT=<MYSQLPORT>`
- `DB_DATABASE=<MYSQLDATABASE>`
- `DB_USERNAME=<MYSQLUSER>`
- `DB_PASSWORD=<MYSQLPASSWORD>`

### Storage

- `AWS_ACCESS_KEY_ID=<R2 Access Key ID>`
- `AWS_SECRET_ACCESS_KEY=<R2 Secret Access Key>`
- `AWS_BUCKET=<R2 bucket name>`
- `AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com`

### Reverb

- `REVERB_APP_KEY=<random key>`
- `REVERB_APP_SECRET=<random secret>`
- `REVERB_HOST=stitch-web.onrender.com`

## 8. Start the first deploy

After saving env values:

1. wait for `stitch-ai` to become healthy
2. wait for `stitch-web` to become healthy

## 9. First live checks

Open:

1. `https://stitch-web.onrender.com/system/version`
2. `https://stitch-web.onrender.com/login`
3. `https://stitch-web.onrender.com/register`

Then test:

1. civilian registration
2. login
3. report submission with photo/selfie/GPS
4. responder login
5. incident feed
6. realtime updates
7. evidence loading from storage

## 10. If AI is failing

Check:

1. `stitch-ai` logs
2. `stitch-web` logs
3. AI service host/port env linkage
4. model checkpoint presence in the AI image

## 11. If uploads fail

Check:

1. `FILESYSTEM_DISK=s3`
2. `AWS_ACCESS_KEY_ID`
3. `AWS_SECRET_ACCESS_KEY`
4. `AWS_BUCKET`
5. `AWS_ENDPOINT`

## 12. If realtime fails

Check:

1. `REVERB_HOST`
2. `REVERB_PORT=443`
3. `REVERB_SCHEME=https`
4. browser websocket errors

## 13. After the default domain is stable

Optional:

1. add a custom domain in Render
2. update:
   - `APP_URL`
   - `REVERB_HOST`
3. redeploy

## 14. Safe first-production rule

For the first deployment, keep:

- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=database`
- `FILESYSTEM_DISK=s3`

After the app is stable, you can add:

1. database queue worker
2. background AI jobs
3. custom domain
