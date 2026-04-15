# Manifest Schema

Each metadata CSV should use these columns in this exact order:

```text
image_relative_path,severity_label,source_domain,municipality,province,barangay,latitude,longitude,incident_type,description,weather,review_status
```

## Column Notes

- `image_relative_path`
  Relative to `datasets/bontoc_southern_leyte/`
  Example: `curated/images/train/serious/sample_001.jpg`
- `severity_label`
  One of `minor`, `serious`, `fatal`
- `source_domain`
  One of `road_accident`, `general_accident`, `emergency_scene`
- `municipality`
  Example: `Bontoc`
- `province`
  Example: `Southern Leyte`
- `barangay`
  Local area or barangay where available
- `latitude`
  Decimal latitude if known
- `longitude`
  Decimal longitude if known
- `incident_type`
  Example: `vehicular_accident`, `motorcycle_collision`, `landslide`
- `description`
  Short human summary
- `weather`
  Example: `clear`, `rain`, `night_rain`, `fog`
- `review_status`
  Suggested values: `approved`, `needs_review`, `rejected`
