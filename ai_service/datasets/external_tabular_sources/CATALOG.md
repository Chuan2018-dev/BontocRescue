# External Tabular Accident Datasets

These datasets were added as supporting structured-data sources for the Stitch AI workspace.

They are useful for:

- analytics
- severity research
- feature engineering
- fallback rule design
- future tabular severity or risk models

They are not direct image datasets for the photo-based accident severity model.

## Included sources

### 1. Road Accident Dataset

- archive: `Road Accident Dataset.zip`
- format: CSV
- main files:
  - `train.csv`
  - `test.csv`
  - `sample_submission.csv`
- main use:
  - road context
  - accident risk prediction
  - weather, lighting, road geometry, and road environment features

### 2. Data on traffic accidents with human victims

- archive: `Data on traffic accidents with human victims.zip`
- format: CSV plus metadata
- main files:
  - `lo_2011_2024.csv`
  - `Liiklus6nnetused_2011_2022.csv`
  - `Liiklusnnetused_2011_2021.csv`
  - `lo_metadata.json`
- main use:
  - human-victim traffic accident analysis
  - fatalities, injuries, and accident context
  - geospatial and municipal accident pattern research

### 3. Traffic Accidents and Vehicles

- archive: `Traffic Accidents and Vehicles.zip`
- format: CSV
- main files:
  - `Accident_Information.csv`
  - `Vehicle_Information.csv`
- main use:
  - severity mapping
  - accident and vehicle feature joins
  - rule extraction for incident severity support

## Important note

For the current civilian photo upload flow, the primary AI severity model still needs labeled image datasets inside:

```text
datasets/bontoc_southern_leyte/curated/images/
```

These imported tabular datasets should be treated as supporting data, not replacements for image severity training data.
