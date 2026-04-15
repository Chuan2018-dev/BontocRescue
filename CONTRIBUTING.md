# Contributing

This repository is now connected to:

- `https://github.com/Chuan2018-dev/BontocRescue`

## Repo structure

- `web_system/` Laravel web app and PWA
- `ai_service/` FastAPI + PyTorch AI service
- `database/` database reference files
- `tool/` local helper scripts
- `01_onboarding/`, `02_auth/`, `03_app_core/`, `04_reporting/` UI reference assets

## Local workflow

From the repo root:

```powershell
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
```

Start the local stack:

```powershell
pwsh -File .\tool\start_stitch_services.ps1
```

Check service status:

```powershell
pwsh -File .\tool\status_stitch_services.ps1
```

Stop the stack:

```powershell
pwsh -File .\tool\stop_stitch_services.ps1
```

## Before pushing

Review changes:

```powershell
git status
git diff --stat
```

Commit and push:

```powershell
git add .
git commit -m "Describe the change"
git push origin main
```

## Do not commit

The root `.gitignore` already excludes local-only material. Do not force-add any of these:

- `.env` files
- `vendor/`
- `node_modules/`
- Laravel runtime files in `web_system/storage/`
- local uploads and generated public storage
- AI checkpoints and torch cache
- raw, extracted, staged, and image-heavy dataset blobs
- Flutter/Dart build output

## Deployment references

Use these files together:

- `RENDER_DEPLOY_QUICKSTART.md`
- `DEPLOYMENT_RENDER_RAILWAY.md`
- `RENDER_RAILWAY_R2_VALUE_MAP.md`
- `PRODUCTION_SECRETS_CHECKLIST.md`
- `GITHUB_RENDER_IMPORT_CHECKLIST.md`

## Production target

Recommended production stack:

1. `Render` for `stitch-web`
2. `Render` private service for `stitch-ai`
3. `Railway MySQL`
4. `Cloudflare R2`

