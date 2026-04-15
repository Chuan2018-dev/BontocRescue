# Local Validation Evaluation

Date: 2026-04-04 21:20:31
Config: `C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch\ai_service\configs\bontoc_southern_leyte_production_candidate_external.yaml`
Experiment: `bontoc_southern_leyte_production_candidate_external`
Checkpoint: `C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch\ai_service\artifacts\checkpoints\bontoc_severity_production_candidate_external_best.pt`
Manifest: `C:\Users\Christian\OneDrive\Pictures\stich\stitch updated\stitch\ai_service\datasets\bontoc_southern_leyte\local_validation\manifests\local_validation_manifest_template.csv`

## Overall

- Total evaluated images: `5`
- Missing image paths: `0`
- Overall accuracy: `0.2000`
- Review-required predictions: `1`

## Per-Class Accuracy

- `minor`
  - total: `1`
  - correct: `0`
  - accuracy: `0.0000`
- `serious`
  - total: `3`
  - correct: `0`
  - accuracy: `0.0000`
- `fatal`
  - total: `1`
  - correct: `1`
  - accuracy: `1.0000`

## Scenario Slices

- `motorcycle`
  - total: `1`
  - correct: `0`
  - accuracy: `0.0000`
- `multi_vehicle`
  - total: `3`
  - correct: `1`
  - accuracy: `0.3333`
- `night_or_low_light`
  - total: `2`
  - correct: `1`
  - accuracy: `0.5000`
- `rural_scene`
  - total: `1`
  - correct: `0`
  - accuracy: `0.0000`

## Confusion Matrix

| true \ predicted | minor | serious | fatal |
| --- | ---: | ---: | ---: |
| minor | 0 | 1 | 0 |
| serious | 0 | 0 | 3 |
| fatal | 0 | 0 | 1 |

## Interpretation Notes

- use this report to find where the current external-only model is weak on local conditions
- focus on motorcycle, night/rain, rural, and multi-vehicle slices first
- do not move these images into training until the validation-only review is complete
