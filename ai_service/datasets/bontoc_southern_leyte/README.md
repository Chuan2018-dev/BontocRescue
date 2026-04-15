# Bontoc, Southern Leyte Dataset

This dataset package is the local operating dataset for the accident severity model.

## Goal

Train a model that helps classify incident images into:

- `minor`
- `serious`
- `fatal`

## Folder Rules

- `raw/`
  Keep original source images grouped by source domain.
- `unlabeled/inbox/`
  Temporary drop zone for new files that still need review.
- `curated/images/`
  Approved and labeled images ready for training.
- `metadata/`
  Manifest CSV files used by the training code.
- `annotations/`
  Labeling rules, class maps, and schema reference.
- `qa/`
  Review logs and annotation quality tracking.

## Exact Severity Folders

```text
curated/images/train/minor
curated/images/train/serious
curated/images/train/fatal
curated/images/val/minor
curated/images/val/serious
curated/images/val/fatal
curated/images/test/minor
curated/images/test/serious
curated/images/test/fatal
```

## Suggested Split Ratio

- train: 70 percent
- val: 15 percent
- test: 15 percent

Keep similar lighting, weather, and road conditions distributed across all three splits when possible.

## Local Context

This dataset should reflect incident conditions commonly seen in and around Bontoc, Southern Leyte:

- barangay roads
- paved and unpaved roads
- motorcycle and tricycle crashes
- jeepney, van, and truck collisions
- rainy weather and slippery roads
- landslide or obstruction scenes near roadways

## Local Sample Status

Earlier live-uploaded Bontoc and civilian evidence files are now treated as `reference-only` samples.

- They are not approved training data
- They are not approved validation data
- They are not approved test data
- They should not be used as the basis for production model evaluation

## Main Manifest Status

The standard local manifests are intentionally empty until a reviewed local dataset is ready:

- `metadata/train_template.csv`
- `metadata/val_template.csv`
- `metadata/test_template.csv`

Use isolated external trials or future reviewed local collections instead of the old sample uploads.
