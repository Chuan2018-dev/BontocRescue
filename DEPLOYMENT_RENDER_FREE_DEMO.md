# Render Free Demo Deployment

Use this profile if your only goal is to test the system online for free and access it from a mobile device.

This profile is for:

1. free `onrender.com` domain
2. mobile browser / PWA access
3. login and registration testing
4. civilian reporting flow testing
5. responder dashboard and realtime testing

This profile is not for stable production use.

## Files

- `render.free-demo.yaml`
- `web_system/.env.render.free-demo.example`

## What this free profile changes

- uses `Render free web service`
- uses `Render free Postgres`
- disables the AI service
- stores uploaded media on local container storage
- keeps Reverb inside the same web container

## Important limits

- the free web service spins down on idle
- cold starts are expected
- local uploaded files can be lost on restart or redeploy
- free Render Postgres expires after `30 days`

## Best use case

Use it to answer these questions:

1. Does the app open online?
2. Does the free Render domain work?
3. Can I log in from phone and laptop?
4. Does the PWA install?
5. Can I submit a report and view it online?

## Steps

### 1. Push current code to GitHub

```powershell
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
git push origin main
```

### 2. Generate APP_KEY

```powershell
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch\web_system"
php artisan key:generate --show
```

Save the generated `base64:...` value.

### 3. Create the Render Blueprint

In Render:

1. click `New`
2. choose `Blueprint`
3. select repo `Chuan2018-dev/BontocRescue`
4. choose blueprint file:
   - `render.free-demo.yaml`

### 4. Fill the required secrets

Render will ask for:

- `APP_URL`
- `APP_KEY`
- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`
- `REVERB_HOST`

Use:

- `APP_URL=https://your-service-name.onrender.com`
- `REVERB_HOST=your-service-name.onrender.com`

### 5. Wait for deploy

Expected resources:

1. `stitch-web-demo`
2. `stitch-demo-db`

### 6. Test the live URL

Open:

1. `https://your-service-name.onrender.com/system/version`
2. `https://your-service-name.onrender.com/login`
3. `https://your-service-name.onrender.com/register`

### 7. Test on mobile

On your Android or iPhone browser:

1. open the same `onrender.com` URL
2. sign in
3. open the civilian report form
4. test the PWA install flow

## What will work

- website online
- free Render domain
- mobile access
- PWA install testing
- login / register
- civilian report submission
- responder web views
- Reverb inside the same web service

## What will not be stable

- AI service
- long-term file retention
- permanent free database

## If you want the full stack later

Use the paid-stack files:

- `render.yaml`
- `DEPLOYMENT_RENDER_RAILWAY.md`

