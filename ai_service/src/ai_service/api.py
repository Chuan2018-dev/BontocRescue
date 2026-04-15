"""FastAPI wrapper for local model inference."""

from __future__ import annotations

from fastapi import FastAPI, File, HTTPException, UploadFile

from .config import load_config
from .defaults import resolve_active_config_path
from .inference import SeverityPredictor

CONFIG_PATH = resolve_active_config_path()

app = FastAPI(title="Stitch AI Severity Service", version="0.1.0")

try:
    CONFIG = load_config(CONFIG_PATH)
    CHECKPOINT_EXISTS = CONFIG.outputs.best_checkpoint_path.exists()
    PREDICTOR = SeverityPredictor(CONFIG) if CHECKPOINT_EXISTS else None
except Exception:
    CONFIG = None
    CHECKPOINT_EXISTS = False
    PREDICTOR = None


@app.get("/health")
def health() -> dict[str, object]:
    return {
        "status": "ok",
        "config_loaded": CONFIG is not None,
        "checkpoint_ready": CHECKPOINT_EXISTS,
        "config_path": str(CONFIG.config_path) if CONFIG is not None else None,
        "experiment_name": CONFIG.experiment.name if CONFIG is not None else None,
        "checkpoint_path": str(CONFIG.outputs.best_checkpoint_path) if CONFIG is not None else None,
        "message": "Upload a checkpoint before using /predict." if not CHECKPOINT_EXISTS else "Prediction service ready.",
    }


@app.post("/predict")
async def predict(file: UploadFile = File(...)) -> dict[str, object]:
    if PREDICTOR is None:
        raise HTTPException(status_code=503, detail="Model checkpoint not available yet. Train the model first.")

    file_bytes = await file.read()
    if not file_bytes:
        raise HTTPException(status_code=400, detail="Uploaded file is empty.")

    prediction = PREDICTOR.predict_bytes(file_bytes)
    return {
        "filename": file.filename,
        "severity": prediction.severity,
        "confidence": round(prediction.confidence, 6),
        "probabilities": prediction.probabilities,
        "responder_review_required": prediction.responder_review_required,
        "responder_review_action": prediction.responder_review_action,
    }
