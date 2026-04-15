# Accident Severity Image Dataset v4

Date imported: 2026-04-04
Source: Kaggle
Dataset slug: `exameese/accident-severity-image-dataset-v4`
License: `CC0-1.0`

## Local Storage

- Archive:
  - `datasets/external_image_sources/archives/accident-severity-image-dataset-v4.zip`
- Extracted:
  - `datasets/external_image_sources/extracted/accident_severity_image_dataset_v4/`

## Extracted Label Folders

- `normal`
- `moderate`
- `severe`

## Recommended Local Mapping

- `normal -> minor`
- `moderate -> serious`
- `severe -> fatal`

## Class Counts

- `normal`: `198`
- `moderate`: `193`
- `severe`: `191`

Total labeled images: `582`

## Quality Snapshot

Sample inspected images are `300x300`, which is a clear improvement over the previously imported low-resolution external trial dataset.

This makes the dataset a stronger candidate for:

- staging review
- controlled trial training
- later merge consideration after duplicate and quality checks

## Safe Recommendation

Do not merge directly into the main production manifests yet.

First do:

1. Copy into a separate staging area
2. Run the quality-review helper
3. Check duplicates against the current Bontoc dataset
4. Run an isolated trial training config
5. Compare performance against the current low-resolution external trial
