# GitHub First Push Commands

Current local project path:

```text
C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch
```

Current state:

- this folder is `not` yet a Git repository

Use these exact commands for the first push.

## 1. Open PowerShell

```bash
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
```

## 2. Initialize Git

```bash
git init
git add .
git commit -m "Prepare Render Railway R2 deployment scaffold"
```

## 3. Create the GitHub repository

At GitHub:

1. Create a new empty repository
2. Do not add a README there
3. Copy the repository URL

Examples:

- `https://github.com/YOUR_USERNAME/stitch-rescue.git`
- `git@github.com:YOUR_USERNAME/stitch-rescue.git`

## 4. Add the remote

Replace the URL below with your real GitHub repository URL:

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
```

## 5. Rename the default branch to main

```bash
git branch -M main
```

## 6. Push the first time

```bash
git push -u origin main
```

## 7. Future updates

After the first push, use:

```bash
cd "C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch"
git add .
git commit -m "Describe your changes here"
git push
```

## 8. Before importing into Render

Confirm these files are already in GitHub:

- `render.yaml`
- `DEPLOYMENT_RENDER_RAILWAY.md`
- `RENDER_RAILWAY_R2_VALUE_MAP.md`
- `PRODUCTION_SECRETS_CHECKLIST.md`
- `GITHUB_RENDER_IMPORT_CHECKLIST.md`
- `web_system/deploy/render/Dockerfile`
- `ai_service/deploy/render/Dockerfile`
