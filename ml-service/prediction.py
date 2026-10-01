from __future__ import annotations

import os
from pathlib import Path
from typing import Any

import joblib
import pandas as pd

from features import COUNT_FEATURES, FEATURE_NAMES, RATE_FEATURES, SUPPORT_LEVELS

DEFAULT_MODEL_PATH = Path(__file__).resolve().parent / "model" / "student_support_model.pkl"


class PredictionValidationError(ValueError):
    def __init__(self, errors: dict[str, str]) -> None:
        super().__init__("The prediction payload is invalid.")
        self.errors = errors


class Predictor:
    def __init__(self, model_path: str | Path | None = None) -> None:
        configured_path = model_path or os.getenv("MODEL_PATH") or DEFAULT_MODEL_PATH
        self.model_path = Path(configured_path)
        self._bundle: dict[str, Any] | None = None

    def is_ready(self) -> bool:
        try:
            self._load_bundle()
        except (FileNotFoundError, OSError, ValueError, KeyError):
            return False

        return True

    def predict(self, payload: Any) -> dict[str, Any]:
        values = self._validate(payload)
        bundle = self._load_bundle()
        model = bundle["model"]
        frame = pd.DataFrame([values], columns=bundle.get("feature_names", FEATURE_NAMES))
        prediction = int(model.predict(frame)[0])
        probabilities = model.predict_proba(frame)[0]
        class_probabilities = {
            SUPPORT_LEVELS[int(class_id)]: round(float(probability), 4)
            for class_id, probability in zip(model.classes_, probabilities, strict=True)
        }

        return {
            "support_level": SUPPORT_LEVELS[prediction],
            "confidence": round(max(class_probabilities.values()), 4),
            "probabilities": class_probabilities,
            "model_version": str(bundle.get("model_version", "unknown")),
        }

    def metadata(self) -> dict[str, Any]:
        bundle = self._load_bundle()

        return {
            "model_version": str(bundle.get("model_version", "unknown")),
            "accuracy": round(float(bundle.get("accuracy", 0)), 4),
            "features": list(bundle.get("feature_names", FEATURE_NAMES)),
        }

    def _load_bundle(self) -> dict[str, Any]:
        if self._bundle is None:
            if not self.model_path.is_file():
                raise FileNotFoundError("The trained model is unavailable. Run train_model.py first.")

            bundle = joblib.load(self.model_path)
            if not isinstance(bundle, dict) or "model" not in bundle:
                raise ValueError("The trained model bundle is invalid.")
            if hasattr(bundle["model"], "n_jobs"):
                bundle["model"].n_jobs = int(os.getenv("MODEL_N_JOBS", "1"))
            self._bundle = bundle

        return self._bundle

    def _validate(self, payload: Any) -> dict[str, float | int]:
        if not isinstance(payload, dict):
            raise PredictionValidationError({"body": "A JSON object is required."})

        errors: dict[str, str] = {}
        values: dict[str, float | int] = {}

        for feature in FEATURE_NAMES:
            value = payload.get(feature)
            if isinstance(value, bool) or not isinstance(value, (int, float)):
                errors[feature] = "A numeric value is required."
                continue

            numeric = float(value)
            if feature in RATE_FEATURES and not 0 <= numeric <= 100:
                errors[feature] = "Must be between 0 and 100."
            elif feature in COUNT_FEATURES and (numeric < 0 or numeric > 100 or not numeric.is_integer()):
                errors[feature] = "Must be a whole number between 0 and 100."
            elif feature == "performance_trend" and not -100 <= numeric <= 100:
                errors[feature] = "Must be between -100 and 100."
            else:
                values[feature] = int(numeric) if feature in COUNT_FEATURES else numeric

        if errors:
            raise PredictionValidationError(errors)

        return values
