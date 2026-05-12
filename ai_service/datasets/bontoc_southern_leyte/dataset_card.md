# Dataset Card

## Name

Bontoc Southern Leyte Accident Severity Dataset

## Intended Use

AI-assisted emergency severity estimation from incident photos for the Stitch system.

## Location Focus

- Municipality: Bontoc
- Province: Southern Leyte
- Country: Philippines

## Prediction Labels

- minor
- serious
- fatal

## Source Domains

- road_accident
- general_accident
- emergency_scene

## Collection Notes

- Prefer photos captured from actual local reporting workflows when consent and policy allow.
- Include daytime, night, rain, and low-visibility scenes.
- Include motorcycles, tricycles, jeepneys, private cars, vans, and trucks.
- Track barangay and road context in the manifest.

## Exclusions

- unrelated images
- duplicate near-identical frames without reason
- corrupted files
- images with uncertain severity and no reviewer agreement
- sample-only live uploads that were collected for smoke testing or UI demonstrations

## Safety Notes

- Model output is assistive only
- Low-confidence predictions should be reviewed by responders
- Fatal labeling should require careful review and documentation
