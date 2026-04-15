# Label Mapping Notes

## Photo severity model

The current accident image model uses:

- `minor`
- `serious`
- `fatal`

## Imported dataset mapping

### Traffic Accidents and Vehicles

Source field:

- `Accident_Severity`

Recommended mapping:

- `Slight` -> `minor`
- `Serious` -> `serious`
- `Fatal` -> `fatal`

### Data on traffic accidents with human victims

This source is event-based and should be transformed from fields like:

- fatalities
- injuries
- number of victims
- accident type

Suggested mapping rule for research only:

- no deaths and low injury indicators -> `minor`
- injuries present without death -> `serious`
- one or more deaths -> `fatal`

This still needs human validation before training.

### Road Accident Dataset

This source appears risk-oriented, not direct severity-labeled.

Useful fields include:

- `road_type`
- `num_lanes`
- `curvature`
- `speed_limit`
- `lighting`
- `weather`
- `time_of_day`
- `accident_risk`

Recommended use:

- support analytics
- feature engineering
- rule design

Do not treat it as a direct replacement for severity labels unless a validated transformation pipeline is added.
