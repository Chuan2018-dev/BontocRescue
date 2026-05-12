# Accident Images Analysis Dataset

## Source

- repository: `mghatee/Accident-Images-Analysis-Dataset`
- source URL: `https://github.com/mghatee/Accident-Images-Analysis-Dataset`

## Purpose

This is a supporting public image dataset for:

- accident detection
- accident severity classification
- vehicle-in-accident classification

## Extracted location in the system

```text
ai_service/datasets/external_image_sources/extracted/accident_images_analysis_dataset/
```

## Main folders

- `Accident -Detection`
- `Accident-Severity`
- `Vehicles-in-Accidents`

## Severity classes

Inside `Accident-Severity`, the dataset readme defines:

- folder `1` -> `low dangerous`
- folder `2` -> `medium dangerous`
- folder `3` -> `high dangerous`

## Recommended local mapping

For the Stitch severity model, use this mapping:

- `1` / `low dangerous` -> `minor`
- `2` / `medium dangerous` -> `serious`
- `3` / `high dangerous` -> `fatal`

## Image counts

- `1` / low dangerous: `118`
- `2` / medium dangerous: `1603`
- `3` / high dangerous: `1225`

## Important note

This dataset is useful, but it should still be reviewed before mixing it directly into the main
`bontoc_southern_leyte/curated/images/` training split.

Reasons:

- class imbalance
- different country/road context
- unknown overlap with local responder priorities
- image quality and labeling should be spot-checked first

## Recommended next step

1. sample-review the images from each class
2. create a cleaned import manifest
3. copy approved images into a separate staging import folder
4. merge only reviewed samples into the local training dataset
