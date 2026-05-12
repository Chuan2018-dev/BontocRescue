# Local Validation Evaluation

Date: 2026-04-04 21:23:16
Config: `C:\laragon\www\CAPSTONE EMERGENCY SYSTEM\stitch updated\stitch\ai_service\configs\bontoc_southern_leyte_production_candidate_external.yaml`
Experiment: `bontoc_southern_leyte_production_candidate_external`
Checkpoint: `C:\laragon\www\CAPSTONE EMERGENCY SYSTEM\stitch updated\stitch\ai_service\artifacts\checkpoints\bontoc_severity_production_candidate_external_best.pt`
Manifest: `C:\laragon\www\CAPSTONE EMERGENCY SYSTEM\stitch updated\stitch\ai_service\datasets\bontoc_southern_leyte\local_validation\manifests\local_validation_manifest_template.csv`

## Overall

- Total evaluated images: `0`
- Missing image paths: `3`
- Overall accuracy: `0.0000`
- Review-required predictions: `0`

## Per-Class Accuracy

- `minor`
  - total: `0`
  - correct: `0`
  - accuracy: `0.0000`
- `serious`
  - total: `0`
  - correct: `0`
  - accuracy: `0.0000`
- `fatal`
  - total: `0`
  - correct: `0`
  - accuracy: `0.0000`

## Scenario Slices


## Confusion Matrix

| true \ predicted | minor | serious | fatal |
| --- | ---: | ---: | ---: |
| minor | 0 | 0 | 0 |
| serious | 0 | 0 | 0 |
| fatal | 0 | 0 | 0 |

## Interpretation Notes

- use this report to find where the current external-only model is weak on local conditions
- focus on motorcycle, night/rain, rural, and multi-vehicle slices first
- do not move these images into training until the validation-only review is complete
