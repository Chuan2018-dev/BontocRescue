# Photo Relevance Gate Recommendations - 2026-05-13

## Problem

The severity classifier can still classify an unrelated or dummy photo as Minor, Serious, or Fatal if the photo relevance gate is unavailable or under-trained.

This is not only a severity accuracy issue. The system needs a separate first step:

1. Check if the uploaded image is a real accident or emergency scene.
2. Reject dummy, app UI, selfie-only, room, food, pet, and unrelated photos.
3. Only then run severity classification.

## Current Findings

- The Laravel app already calls the AI service before storing civilian photo reports as accepted.
- If the AI service rejects the image, Laravel returns a validation error.
- If the AI service is unavailable and strict gate mode is disabled, Laravel falls back to description-based severity, so dummy photos can pass.
- The photo relevance training set is imbalanced:
  - train related: 405
  - train unrelated: 26
  - validation unrelated: 7
  - test unrelated: 7
- The model can report high overall accuracy while still missing real-world dummy examples because there are too few negative examples.

## Changes Applied

- Added a Laravel server-side pre-screen for obvious dummy evidence:
  - screenshots
  - app UI / dashboard / login / settings images
  - profile, selfie, avatar, icon, and logo-like files
  - food, pet, room, and other filename-based dummy indicators
  - screenshot-like tall image ratios
- Added `AI_SEVERITY_REQUIRE_CIVILIAN_PHOTO_GATE`.
  - When true, civilian photo reports are not accepted if the AI photo gate is unavailable.
  - This prevents dummy photos from silently passing during AI downtime.
- Lowered the photo relevance reject threshold:
  - `reject_threshold`: `0.92` to `0.70`
  - `low_confidence_threshold`: `0.65` to `0.75`

## Recommended Dataset Fix

Add at least 200 to 500 reviewed negative examples before trusting dummy rejection.

Recommended negative classes:

- phone screenshots of the PWA
- Messenger, Facebook, TikTok, and browser UI screenshots
- normal selfies
- profile photos
- bedrooms, kitchens, classrooms, offices, walls, and ceilings
- food, pets, plants, documents, receipts, IDs
- random motorcycles or vehicles with no accident
- normal roads with no incident
- blurred or dark photos where no scene can be confirmed

Recommended positive classes:

- real motorcycle crashes
- real multi-vehicle crashes
- roadside injuries
- night/rain road incidents
- rural road emergencies
- Philippine-like roads and Bontoc/Southern Leyte-like environments

## Recommended Split

Keep the relevance gate balanced:

- train: roughly 50 percent related, 50 percent unrelated
- validation: at least 50 unrelated examples
- test: at least 50 unrelated examples

Do not measure only overall accuracy. Track:

- unrelated recall
- false accept rate for dummy photos
- false reject rate for real accident photos

## Deployment Recommendation

For online testing, the AI service must have the trained checkpoint available in deployment.

Current repo behavior:

- AI checkpoints are ignored by Git under `ai_service/artifacts/checkpoints/`.
- If Render deploys without the checkpoint, `/predict` cannot run the real model.

Best options:

- Use Git LFS for selected `.pt` checkpoints.
- Or store checkpoints in R2/S3 and download them during AI service startup.
- Or attach a persistent Render disk to the AI service and upload the checkpoints there.

Without this, online dummy-photo rejection cannot be trusted because the web app may be running without the trained relevance model.
