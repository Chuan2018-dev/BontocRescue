"""FastAPI wrapper for local model inference."""

from __future__ import annotations

import os
import sys

from fastapi import FastAPI, File, HTTPException, UploadFile

from .checkpoint_sync import is_usable_checkpoint
from .config import load_config
from .defaults import resolve_active_config_path, resolve_photo_relevance_config_path
from .inference import PhotoRelevancePredictor, SeverityPredictor

CONFIG_PATH = resolve_active_config_path()
RELEVANCE_CONFIG_PATH = resolve_photo_relevance_config_path()

app = FastAPI(title="Stitch AI Severity Service", version="0.3.0")

try:
    CONFIG = load_config(CONFIG_PATH)
    CHECKPOINT_EXISTS = is_usable_checkpoint(CONFIG.outputs.best_checkpoint_path)
except Exception:
    CONFIG = None
    CHECKPOINT_EXISTS = False

try:
    RELEVANCE_CONFIG = load_config(RELEVANCE_CONFIG_PATH)
    RELEVANCE_CHECKPOINT_EXISTS = is_usable_checkpoint(RELEVANCE_CONFIG.outputs.best_checkpoint_path)
except Exception:
    RELEVANCE_CONFIG = None
    RELEVANCE_CHECKPOINT_EXISTS = False

PREDICTOR: SeverityPredictor | None = None
RELEVANCE_PREDICTOR: PhotoRelevancePredictor | None = None


def truthy_env(name: str, default: bool = False) -> bool:
    value = os.getenv(name)
    if value is None:
        return default

    return value.strip().lower() in {"1", "true", "yes", "on"}


def float_env(name: str, default: float) -> float:
    value = os.getenv(name)
    if value is None:
        return default

    try:
        return float(value)
    except ValueError:
        return default


def severity_predictor() -> SeverityPredictor:
    global PREDICTOR

    if CONFIG is None or not CHECKPOINT_EXISTS:
        raise HTTPException(status_code=503, detail="Model checkpoint not available yet. Train or sync the model first.")

    if PREDICTOR is None:
        PREDICTOR = SeverityPredictor(CONFIG)

    return PREDICTOR


def relevance_predictor() -> PhotoRelevancePredictor | None:
    global RELEVANCE_PREDICTOR

    if RELEVANCE_CONFIG is None or not RELEVANCE_CHECKPOINT_EXISTS:
        return None

    if RELEVANCE_PREDICTOR is None:
        RELEVANCE_PREDICTOR = PhotoRelevancePredictor(RELEVANCE_CONFIG)

    return RELEVANCE_PREDICTOR


def warm_predictors() -> dict[str, object]:
    photo_gate = relevance_predictor()
    predictor = severity_predictor()

    return {
        "status": "ready",
        "severity_predictor_loaded": predictor is not None,
        "photo_relevance_predictor_loaded": photo_gate is not None,
    }


@app.on_event("startup")
def preload_models() -> None:
    if not truthy_env("AI_PRELOAD_MODELS", default=False):
        return

    try:
        warm_predictors()
    except Exception as exc:
        print(f"[ai-service] model preload failed: {exc}", file=sys.stderr, flush=True)

        if truthy_env("AI_PRELOAD_MODELS_REQUIRED", default=False):
            raise


@app.get("/health")
def health() -> dict[str, object]:
    return {
        "status": "ok",
        "config_loaded": CONFIG is not None,
        "checkpoint_ready": CHECKPOINT_EXISTS,
        "config_path": str(CONFIG.config_path) if CONFIG is not None else None,
        "experiment_name": CONFIG.experiment.name if CONFIG is not None else None,
        "checkpoint_path": str(CONFIG.outputs.best_checkpoint_path) if CONFIG is not None else None,
        "photo_relevance_config_loaded": RELEVANCE_CONFIG is not None,
        "photo_relevance_checkpoint_ready": RELEVANCE_CHECKPOINT_EXISTS,
        "photo_relevance_config_path": str(RELEVANCE_CONFIG.config_path) if RELEVANCE_CONFIG is not None else None,
        "photo_relevance_experiment_name": RELEVANCE_CONFIG.experiment.name if RELEVANCE_CONFIG is not None else None,
        "photo_relevance_checkpoint_path": str(RELEVANCE_CONFIG.outputs.best_checkpoint_path) if RELEVANCE_CONFIG is not None else None,
        "severity_predictor_loaded": PREDICTOR is not None,
        "photo_relevance_predictor_loaded": RELEVANCE_PREDICTOR is not None,
        "message": "Upload a checkpoint before using /predict." if not CHECKPOINT_EXISTS else "Prediction service ready.",
    }


@app.get("/warmup")
def warmup() -> dict[str, object]:
    return warm_predictors()


@app.post("/predict")
async def predict(file: UploadFile = File(...)) -> dict[str, object]:
    file_bytes = await file.read()
    if not file_bytes:
        raise HTTPException(status_code=400, detail="Uploaded file is empty.")

    photo_gate = relevance_predictor()
    relevance_overridden = False
    if photo_gate is not None:
        relevance = photo_gate.predict_bytes(file_bytes)
        if not relevance.accepted:
            predictor = severity_predictor()
            prediction = predictor.predict_bytes(file_bytes)
            override_threshold = float_env("AI_RELEVANCE_SEVERITY_OVERRIDE_THRESHOLD", 0.70)

            if prediction.confidence >= override_threshold and not prediction.responder_review_required:
                relevance_overridden = True
            else:
                return {
                    "filename": file.filename,
                    "accepted": False,
                    "photo_relevance_label": relevance.label,
                    "photo_relevance_confidence": round(relevance.confidence, 6),
                    "photo_relevance_probabilities": relevance.probabilities,
                    "photo_relevance_overridden": False,
                    "rejection_message": relevance.rejection_message,
                    "responder_review_required": relevance.responder_review_required,
                    "responder_review_action": relevance.responder_review_action,
                    "severity": None,
                    "confidence": 0.0,
                    "probabilities": {},
                }
        else:
            predictor = severity_predictor()
            prediction = predictor.predict_bytes(file_bytes)
    else:
        relevance = None
        predictor = severity_predictor()
        prediction = predictor.predict_bytes(file_bytes)

    return {
        "filename": file.filename,
        "accepted": True,
        "photo_relevance_label": relevance.label if relevance is not None else "related",
        "photo_relevance_confidence": round(relevance.confidence, 6) if relevance is not None else None,
        "photo_relevance_probabilities": relevance.probabilities if relevance is not None else {},
        "photo_relevance_overridden": relevance_overridden,
        "rejection_message": None,
        "severity": prediction.severity,
        "confidence": round(prediction.confidence, 6),
        "probabilities": prediction.probabilities,
        "responder_review_required": prediction.responder_review_required,
        "responder_review_action": prediction.responder_review_action,
    }
