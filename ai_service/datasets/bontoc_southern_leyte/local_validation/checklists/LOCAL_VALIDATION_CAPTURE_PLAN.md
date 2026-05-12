# Local Validation Capture Plan

Use this plan when building the first `Bontoc / Southern Leyte` validation-only image set.

## Objective

Build a local holdout set that tests how well the current external-trained model performs on actual target conditions.

## Recommended Phases

### Phase 1

- `20` minor
- `20` serious
- `20` fatal

### Phase 2

Expand to:

- `40` to `60` per class

## Scenario Quotas

Try to hit these minimum scenario counts in the first pass:

- motorcycle incidents: `10+`
- night scenes: `10+`
- rain or wet-road scenes: `10+`
- rural/barangay road scenes: `15+`
- multi-vehicle scenes: `10+`

These can overlap in the same image.

## Local Context Targets

Prioritize images that look like:

- Bontoc or Southern Leyte road geometry
- provincial roads
- narrow barangay roads
- mixed motorcycle, tricycle, van, jeep, and truck traffic
- roadside vegetation, curves, slopes, and limited lighting

## Reviewer Workflow

1. assign provisional label
2. check against labeling guide
3. verify if the image is useful for local validation
4. log quality notes
5. place in the correct class folder
6. add metadata to the manifest

## Evaluation Goal

This set should answer:

- where the current model is strong
- where it overpredicts
- where it misses local patterns
- whether `v0.3` needs more motorcycles, night scenes, rain scenes, or rural-road examples
