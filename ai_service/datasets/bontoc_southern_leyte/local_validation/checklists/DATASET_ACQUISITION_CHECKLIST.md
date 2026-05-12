# Dataset Acquisition Checklist

Use this checklist when collecting or reviewing new accident and emergency images for the `Bontoc, Southern Leyte` severity model.

## Goal

We want `high-quality`, `reviewed`, and `realistic` images that are closer to:

- Philippine roads
- motorcycles
- multi-vehicle crashes
- nighttime or rain conditions
- rural emergency scenes

## Source Rules

- prefer images with clear permission or safe public reuse terms
- document where each image came from
- avoid unknown scraped images with unclear rights
- keep a source note for every imported batch
- do not mix training and validation images from the same near-duplicate sequence

## Quality Rules

Include images that are:

- at least moderate resolution
- not heavily pixelated
- not duplicate or near-duplicate when possible
- not mostly text, watermark, or meme overlays
- realistic phone-style captures when possible

Avoid images that are:

- tiny thumbnails
- heavily edited
- collage images with many unrelated scenes
- cartoon or synthetic unless explicitly marked for a separate experiment

## Required Scenario Coverage

Try to collect examples for each of these:

- paved national roads
- barangay roads
- mountain or curved roads
- wet roads after rain
- nighttime scenes
- low-light roadside captures
- motorcycle-only crashes
- motorcycle versus car
- motorcycle versus truck or van
- tricycle incidents
- multi-vehicle collisions
- roadside ditch or embankment crashes
- blocked-road incidents
- rural emergency scenes with limited lighting

## Severity Coverage

For each scenario, try to find examples that fit:

- `minor`
- `serious`
- `fatal`

Do not let one class dominate the collection too much.

## Labeling Rules

Before accepting an image:

- confirm the label using the project labeling guide
- note if the scene is ambiguous
- note if the image only partially shows the incident
- note if injuries are not visible and severity is inferred from context

## Metadata To Record

For each accepted image, record when possible:

- source dataset or link
- municipality
- province
- barangay
- road type
- weather
- lighting condition
- vehicle types involved
- whether motorcycles are present
- whether the scene is rural
- whether it is a multi-vehicle crash
- reviewer name
- final severity label

## Local Priority Order

If time is limited, prioritize this order:

1. motorcycle crashes
2. night and rain conditions
3. rural and barangay roads
4. multi-vehicle collisions
5. partial-view or low-quality phone captures

## Acceptance Checklist

Accept the image only if:

- it is relevant to real incident severity
- it is not an obvious duplicate
- the label is defensible
- the image adds scenario diversity
- the source is documented

## Red Flags

Stop and review before using the image if:

- the label is unclear
- the image is from a dramatic news montage
- the same incident appears many times from almost the same angle
- the severity class was guessed without enough evidence
- the scene looks outside the target domain
