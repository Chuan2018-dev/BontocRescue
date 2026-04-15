"""Shared label helpers for the accident severity service."""

from __future__ import annotations

SEVERITY_LABELS = ("minor", "serious", "fatal")
SOURCE_DOMAINS = ("road_accident", "general_accident", "emergency_scene")

DISPLAY_LABELS = {
    "minor": "Minor",
    "serious": "Serious",
    "fatal": "Fatal",
}


def normalize_severity_label(label: str) -> str:
    normalized = label.strip().lower()
    if normalized not in SEVERITY_LABELS:
        raise ValueError(f"Unsupported severity label: {label!r}")
    return normalized


def normalize_source_domain(domain: str) -> str:
    normalized = domain.strip().lower()
    if normalized not in SOURCE_DOMAINS:
        raise ValueError(f"Unsupported source domain: {domain!r}")
    return normalized
