# Bontoc Local Validation Set

This folder is reserved for `validation-only` images that represent the real operating context in `Bontoc, Southern Leyte`.

Important:

- do not use this folder as part of the training set
- do not merge these images into `curated/images/train`, `val`, or `test`
- use this folder to measure how the current model behaves on realistic local cases

## Purpose

The current active AI baseline is built from approved external data. That is useful for a starting point, but we still need a local validation-only set to answer the real question:

`Does the model behave well on Bontoc and Southern Leyte incident photos?`

This folder helps us evaluate that safely before we train a future `v0.3` model.

## Folder Layout

```text
local_validation/
  images/
    minor/
    serious/
    fatal/
  manifests/
    local_validation_manifest_template.csv
    scenario_coverage_template.csv
  review/
    local_validation_review_template.csv
  reports/
  checklists/
    DATASET_ACQUISITION_CHECKLIST.md
    LOCAL_VALIDATION_CAPTURE_PLAN.md
  LOCAL_VALIDATION_RUNNER_GUIDE.md
```

## How To Use

1. Gather local or Philippines-like photos that match the checklist.
2. Place reviewed images in the correct severity folder:
   - `images/minor`
   - `images/serious`
   - `images/fatal`
3. Fill in `manifests/local_validation_manifest_template.csv`.
4. Track quality issues in `review/local_validation_review_template.csv`.
5. Track scenario coverage in `manifests/scenario_coverage_template.csv`.
6. Run manual or batch prediction against these images.
7. Save findings in `reports/`.

For the exact command flow, use:

- `LOCAL_VALIDATION_RUNNER_GUIDE.md`

## Suggested Evaluation Rules

- never tune labels after seeing the model output
- keep labels based on responder review, not AI prediction
- include hard cases, not just obvious scenes
- include low-light, rain, glare, blur, and partial-frame phone captures
- include motorcycles, rural roads, and multi-vehicle scenes

## Minimum First Target

For the first local validation pass, aim for at least:

- `20` minor
- `20` serious
- `20` fatal

That is still small, but enough for a first honest evaluation pass.

## Stronger Target For v0.3 Preparation

- `40` to `60` images per class
- with clear coverage of:
  - motorcycle crashes
  - night incidents
  - rain or wet roads
  - rural/barangay roads
  - multi-vehicle collisions
  - obstructed and partial-view scenes
