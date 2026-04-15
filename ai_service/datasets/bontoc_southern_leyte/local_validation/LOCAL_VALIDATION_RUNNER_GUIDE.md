# Local Validation Runner Guide

Use this guide after you place real Bontoc or Southern Leyte evaluation images inside `local_validation/images/`.

## Goal

Measure how well the current active model works on your actual local context before retraining.

## Step 1: Add images

Place reviewed images here:

- `local_validation/images/minor`
- `local_validation/images/serious`
- `local_validation/images/fatal`

## Step 2: Fill the manifest

Update:

- `local_validation/manifests/local_validation_manifest_template.csv`

Important:

- `severity_label` must be `minor`, `serious`, or `fatal`
- `image_relative_path` should point to the file under `local_validation/images/...`
- fill scenario fields when possible:
  - `road_type`
  - `weather`
  - `lighting_condition`
  - `vehicle_types`
  - `motorcycle_present`
  - `multi_vehicle`
  - `rural_scene`

## Step 3: Optional review tracking

If you want to track quality notes, use:

- `local_validation/review/local_validation_review_template.csv`

## Step 4: Run the evaluator

Inside `ai_service/`:

```powershell
cd "C:\laragon\www\CAPSTONE EMERGENCY SYSTEM\stitch updated\stitch\ai_service"
C:\laragon\bin\python\python-3.13\python.exe tools\evaluate_local_validation.py
```

## Step 5: Check outputs

The evaluator writes reports here:

- `datasets/bontoc_southern_leyte/local_validation/reports/`

You will get:

- one CSV with per-image prediction results
- one markdown summary with:
  - overall accuracy
  - per-class accuracy
  - confusion matrix
  - scenario slice accuracy

## Step 6: Use the summary for decisions

Look first at:

- motorcycle accuracy
- night or low-light accuracy
- rain or wet-road accuracy
- rural or barangay-road accuracy
- multi-vehicle accuracy

If those are weak, collect more targeted data before retraining.

## Good Practice

- keep this set `validation-only`
- do not move these images into training
- do not relabel after seeing model output
- let responder review decide the ground-truth label

## Recommended Next Step

After the local validation report is generated:

1. list the weakest scenario slices
2. add more reviewed images into `v0_3_candidate/reviewed_pool/`
3. run the v0.3 split builder after enough approved images are ready
