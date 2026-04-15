# Render Deploy Quickstart

This is the shortest safe path to put `BontocRescue` online.

## Current GitHub repo

- `https://github.com/Chuan2018-dev/BontocRescue`

## Target stack

1. `Render` blueprint for:
   - `stitch-web`
   - `stitch-ai`
2. `Railway MySQL`
3. `Cloudflare R2`

## 1. Confirm the repo is current

From the repo root:

```powershell
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
git remote -v
git status
git push origin main
```

Expected remote:

```text
origin  https://github.com/Chuan2018-dev/BontocRescue.git
```

## 2. Create Railway MySQL

In Railway:

1. Create a new project
2. Add `MySQL`
3. Copy these values:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

## 3. Create the R2 bucket

In Cloudflare:

1. Create one bucket
2. Create one access key pair
3. Copy:
   - `Access Key ID`
   - `Secret Access Key`
   - bucket name
   - account endpoint

## 4. Generate the Laravel APP_KEY

From `web_system/`:

```powershell
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch\web_system"
php artisan key:generate --show
```

Save the generated `base64:...` value.

## 5. Prepare the secrets sheet

Fill this file before opening Render:

- `PRODUCTION_SECRETS_CHECKLIST.md`

Map values using:

- `RENDER_RAILWAY_R2_VALUE_MAP.md`

## 6. Import the Render blueprint

In Render:

1. Click `New`
2. Choose `Blueprint`
3. Connect GitHub if needed
4. Select:
   - `Chuan2018-dev/BontocRescue`
5. Render should detect:
   - `render.yaml`

Expected services:

- `stitch-web`
- `stitch-ai`

## 7. Fill the required Render environment variables

Use these exact mappings.

### App

- `APP_URL=https://stitch-web.onrender.com`
- `APP_KEY=<generated base64 key>`

### Database

- `DB_HOST=<MYSQLHOST>`
- `DB_PORT=<MYSQLPORT>`
- `DB_DATABASE=<MYSQLDATABASE>`
- `DB_USERNAME=<MYSQLUSER>`
- `DB_PASSWORD=<MYSQLPASSWORD>`

### Object storage

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

1. Wait for `stitch-ai` to become healthy
2. Wait for `stitch-web` to finish deploying

## 9. First production checks

Open and verify:

1. `https://stitch-web.onrender.com/system/version`
2. `https://stitch-web.onrender.com/login`
3. `https://stitch-web.onrender.com/register`

Then test:

1. civilian registration
2. civilian login
3. report submission
4. file upload
5. responder login
6. realtime incident updates
7. AI-backed severity processing

## 10. If you make another repo change

Use this exact workflow:

```powershell
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
git status
git add .
git commit -m "Describe the change"
git push origin main
```

Render will redeploy from GitHub.

## Reference files

- `DEPLOYMENT_RENDER_RAILWAY.md`
- `RENDER_RAILWAY_R2_VALUE_MAP.md`
- `PRODUCTION_SECRETS_CHECKLIST.md`
- `GITHUB_RENDER_IMPORT_CHECKLIST.md`

