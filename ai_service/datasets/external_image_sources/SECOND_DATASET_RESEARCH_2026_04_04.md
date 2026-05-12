# Second Dataset Research

Date: 2026-04-04
Goal: identify a second higher-resolution public image dataset before any merge into the main production severity set.

## Current Situation

The first imported public dataset is useful for experimentation, but all staged images are `28x28`, which is too small for confident severity analysis.

We need a second dataset with better visual detail so the model can learn stronger cues such as:

- major body deformation
- broken glass
- rollover damage
- fire or smoke indicators
- multi-vehicle impact severity

## Best Candidate

### CarDD

Official project:

- `https://cardd-ustc.github.io/`

Why it stands out:

- `4,000` high-resolution images
- `9,000+` annotated damage instances
- average resolution reported as `684,231` pixels
- minimum resolution reported as `1000x413`
- strong visual detail compared with older accident image sets

Important limitation:

- labels are damage categories, not direct `minor / serious / fatal`
- this means CarDD should not be merged directly into the main severity dataset without an explicit mapping and review workflow

Best use in this project:

- auxiliary pretraining
- damage-aware feature extraction
- review-safe staging for later severity remapping

## Good Label-Fit Candidate

### Accident Severity Image Dataset v4

Dataset page:

- `https://www.kaggle.com/datasets/exameese/accident-severity-image-dataset-v4`

Why it is useful:

- label structure already matches the project closely:
  - `normal`
  - `moderate`
  - `severe`
- practical mapping:
  - `normal -> minor`
  - `moderate -> serious`
  - `severe -> fatal`
- license shown as `CC0`

Important limitation:

- image resolution is not clearly documented in the searchable dataset card we reviewed
- the dataset should be downloaded into staging first and checked with the same quality-review helper before any merge

## Good Future Accident-Scene Candidate

### TUM Traffic Accid3nD

Project page:

- `https://accident-dataset.github.io/`

Why it is promising:

- real-world accident recordings
- `111,945` labeled frames
- roadside camera images at `1920x1200`
- includes collisions, rollovers, and fire scenes

Important limitation:

- this is an accident detection dataset, not a direct severity classification dataset
- it is stronger for future crash detection and scene understanding than for immediate `minor / serious / fatal` training

## Safe Recommendation Order

1. Stage `CarDD` as the second higher-resolution public dataset
2. Stage `Accident Severity Image Dataset v4` if download access is available
3. Keep both external sources separate from the main production manifests until:
   - resolution review passes
   - duplicate checks pass
   - label mapping is documented
   - a trial run shows better validation performance than the current low-resolution external trial

## Merge Rule

No external dataset should be merged into the main production severity manifests until it passes:

- image quality review
- duplicate review
- label mapping review
- isolated trial training
- manual spot-check against Bontoc capture conditions
