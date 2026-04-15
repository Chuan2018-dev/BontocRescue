"""Prediction helpers for the trained severity model."""

from __future__ import annotations

from dataclasses import dataclass
from io import BytesIO
from pathlib import Path

import torch
from PIL import Image

from .config import AppConfig, load_config
from .dataset import build_transforms
from .model import build_model


@dataclass
class SeverityPrediction:
    severity: str
    confidence: float
    responder_review_required: bool
    responder_review_action: str
    probabilities: dict[str, float]


class SeverityPredictor:
    def __init__(self, config: AppConfig, checkpoint_path: Path | None = None) -> None:
        self.config = config
        self.device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
        self.checkpoint_path = checkpoint_path or config.outputs.best_checkpoint_path
        checkpoint = torch.load(self.checkpoint_path, map_location=self.device, weights_only=False)

        self.labels = tuple(checkpoint["labels"])
        self.model = build_model(
            num_classes=len(self.labels),
            pretrained=False,
        ).to(self.device)
        self.model.load_state_dict(checkpoint["model_state_dict"])
        self.model.eval()
        self.transform = build_transforms(image_size=config.dataset.image_size, training=False)

    @classmethod
    def from_config(cls, config_path: str | Path) -> "SeverityPredictor":
        return cls(load_config(config_path))

    def predict_image(self, image: Image.Image) -> SeverityPrediction:
        tensor = self.transform(image.convert("RGB")).unsqueeze(0).to(self.device)

        with torch.no_grad():
            logits = self.model(tensor)
            probabilities_tensor = torch.softmax(logits, dim=1).squeeze(0).cpu()

        probabilities = {
            label: float(probabilities_tensor[index].item())
            for index, label in enumerate(self.labels)
        }
        best_index = int(torch.argmax(probabilities_tensor).item())
        best_label = self.labels[best_index]
        confidence = probabilities[best_label]
        review_required = confidence < self.config.inference.low_confidence_threshold

        return SeverityPrediction(
            severity=best_label,
            confidence=confidence,
            responder_review_required=review_required,
            responder_review_action=self.config.inference.responder_review_action,
            probabilities=probabilities,
        )

    def predict_path(self, image_path: str | Path) -> SeverityPrediction:
        image = Image.open(image_path).convert("RGB")
        return self.predict_image(image)

    def predict_bytes(self, image_bytes: bytes) -> SeverityPrediction:
        image = Image.open(BytesIO(image_bytes)).convert("RGB")
        return self.predict_image(image)
