# v0.3 Training Candidate

This folder is the staging area for the next `v0.3` severity retraining cycle.

Use it only for `reviewed training candidates`, not for random collected images.

## Purpose

The current active model is still an `external-only` baseline.

This folder prepares the next retraining wave that should improve performance on:

- motorcycles
- night or low-light incidents
- rain or wet-road scenes
- rural and barangay roads
- multi-vehicle crashes

## Folder Layout

```text
v0_3_candidate/
  reviewed_pool/
    minor/
    serious/
    fatal/
  images/
    train/
      minor/
      serious/
      fatal/
    val/
      minor/
      serious/
      fatal/
    test/
      minor/
      serious/
      fatal/
  manifests/
    reviewed_pool_manifest_template.csv
    train_v0_3_candidate_template.csv
    val_v0_3_candidate_template.csv
    test_v0_3_candidate_template.csv
    scenario_balance_tracker.csv
  review/
    v0_3_candidate_review_template.csv
  reports/
  source_batches/
```

## Recommended Workflow

1. Collect new higher-quality images.
2. Keep raw source notes in `source_batches/`.
3. Review and approve images first.
4. Place approved images in `reviewed_pool/{label}/`.
5. Fill in `reviewed_pool_manifest_template.csv`.
6. Track issues in `review/v0_3_candidate_review_template.csv`.
7. Build the final `train`, `val`, and `test` split from the approved pool.
8. Fill in the train/val/test manifest templates.
9. Train using the `v0.3` config template.

## Important Rules

- do not place unreviewed images directly into `images/train`, `val`, or `test`
- do not copy `local_validation/` images into this candidate set
- keep motorcycle, night, rain, rural, and multi-vehicle coverage visible in the metadata
- keep classes balanced as much as possible

## First v0.3 Target

Aim for a reviewed pool that adds at least:

- `30+` motorcycle-related scenes
- `20+` night or low-light scenes
- `20+` rain or wet-road scenes
- `25+` rural or barangay-road scenes
- `20+` multi-vehicle scenes

These may overlap in the same images.
