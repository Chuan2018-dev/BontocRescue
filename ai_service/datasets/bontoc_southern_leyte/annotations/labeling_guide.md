# Labeling Guide

Use this guide to keep severity labels consistent across reviewers.

## Core Labels

### minor

Typical cues:

- limited vehicle damage
- no visible life-threatening injury cues
- road remains mostly passable
- responder support still useful but not obviously critical from the image

### serious

Typical cues:

- major collision damage
- likely urgent injury based on visible scene context
- victim may require rapid transport
- vehicle rollover, crushed front or side impact, or blocked roadway
- unstable scene needing fast responder coordination

### fatal

Typical cues:

- extreme destruction
- very high likelihood of death or catastrophic trauma cues
- multi-vehicle catastrophic impact
- severe entrapment or obvious mass-casualty indicators

## Important Rule

If the image does not clearly support a fatal label, do not force `fatal`.

Use:

- `serious` when severe harm is likely but not visually certain
- responder review when confidence is low

## Source Domain Rules

- `road_accident`
  Vehicle crashes on roads, highways, intersections, or barangay routes.
- `general_accident`
  Non-road accident scenes still relevant to emergency response.
- `emergency_scene`
  Obstruction, landslide, flooding, collapse, or other emergency incident scenes.

## Review Workflow

1. Label the image independently.
2. If the reviewer is unsure, flag it for secondary review.
3. Record disagreements in `qa/review_log_template.csv`.
4. Approve only images with stable final labels.

## Bontoc-Specific Checks

Before finalizing a label, consider:

- Is this a narrow barangay road where road blockage raises urgency?
- Is weather making the scene more dangerous?
- Is the capture too dark or too distant to justify a strong label?
- Is the image showing aftermath only, not the true impact moment?
