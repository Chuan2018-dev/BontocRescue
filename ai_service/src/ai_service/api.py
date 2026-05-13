"""FastAPI wrapper for local model inference."""

from __future__ import annotations

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
    PREDICTOR = SeverityPredictor(CONFIG) if CHECKPOINT_EXISTS else None
except Exception:
    CONFIG = None
    CHECKPOINT_EXISTS = False
    PREDICTOR = None

try:
    RELEVANCE_CONFIG = load_config(RELEVANCE_CONFIG_PATH)
    RELEVANCE_CHECKPOINT_EXISTS = is_usable_checkpoint(RELEVANCE_CONFIG.outputs.best_checkpoint_path)
    RELEVANCE_PREDICTOR = PhotoRelevancePredictor(RELEVANCE_CONFIG) if RELEVANCE_CHECKPOINT_EXISTS else None
except Exception:
    RELEVANCE_CONFIG = None
    RELEVANCE_CHECKPOINT_EXISTS = False
    RELEVANCE_PREDICTOR = None


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
        "message": "Upload a checkpoint before using /predict." if not CHECKPOINT_EXISTS else "Prediction service ready.",
    }


@app.post("/predict")
async def predict(file: UploadFile = File(...)) -> dict[str, object]:
    if PREDICTOR is None:
        raise HTTPException(status_code=503, detail="Model checkpoint not available yet. Train the model first.")

    file_bytes = await file.read()
    if not file_bytes:
        raise HTTPException(status_code=400, detail="Uploaded file is empty.")

    if RELEVANCE_PREDICTOR is not None:
        relevance = RELEVANCE_PREDICTOR.predict_bytes(file_bytes)
        if not relevance.accepted:
            return {
                "filename": file.filename,
                "accepted": False,
                "photo_relevance_label": relevance.label,
                "photo_relevance_confidence": round(relevance.confidence, 6),
                "photo_relevance_probabilities": relevance.probabilities,
                "rejection_message": relevance.rejection_message,
                "responder_review_required": relevance.responder_review_required,
                "responder_review_action": relevance.responder_review_action,
                "severity": None,
                "confidence": 0.0,
                "probabilities": {},
            }
    else:
        relevance = None

    prediction = PREDICTOR.predict_bytes(file_bytes)
    return {
        "filename": file.filename,
        "accepted": True,
        "photo_relevance_label": relevance.label if relevance is not None else "related",
        "photo_relevance_confidence": round(relevance.confidence, 6) if relevance is not None else None,
        "photo_relevance_probabilities": relevance.probabilities if relevance is not None else {},
        "rejection_message": None,
        "severity": prediction.severity,
        "confidence": round(prediction.confidence, 6),
        "probabilities": prediction.probabilities,
        "responder_review_required": prediction.responder_review_required,
        "responder_review_action": prediction.responder_review_action,
    }
