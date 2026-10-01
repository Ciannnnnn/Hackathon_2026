from __future__ import annotations

import unittest

from app import create_app
from prediction import PredictionValidationError


class FakePredictor:
    def is_ready(self) -> bool:
        return True

    def metadata(self) -> dict[str, object]:
        return {
            "model_version": "test-model",
            "accuracy": 0.95,
            "features": ["attendance_rate"],
        }

    def predict(self, payload: object) -> dict[str, object]:
        if not isinstance(payload, dict) or "attendance_rate" not in payload:
            raise PredictionValidationError({"attendance_rate": "A numeric value is required."})

        return {
            "support_level": "HIGH",
            "confidence": 0.87,
            "probabilities": {"LOW": 0.03, "MODERATE": 0.1, "HIGH": 0.87},
            "model_version": "test-model",
        }


class PredictionApiTest(unittest.TestCase):
    def setUp(self) -> None:
        application = create_app(FakePredictor())
        application.config.update(TESTING=True)
        self.client = application.test_client()

    def test_health_endpoint_reports_model_metadata(self) -> None:
        response = self.client.get("/health")

        self.assertEqual(200, response.status_code)
        self.assertEqual("ok", response.json["status"])
        self.assertEqual("test-model", response.json["model_version"])

    def test_predict_endpoint_returns_a_support_level(self) -> None:
        response = self.client.post("/predict", json={"attendance_rate": 68})

        self.assertEqual(200, response.status_code)
        self.assertEqual("HIGH", response.json["support_level"])
        self.assertEqual(0.87, response.json["confidence"])

    def test_predict_endpoint_rejects_invalid_json(self) -> None:
        response = self.client.post("/predict", json={})

        self.assertEqual(422, response.status_code)
        self.assertIn("attendance_rate", response.json["errors"])


if __name__ == "__main__":
    unittest.main()
