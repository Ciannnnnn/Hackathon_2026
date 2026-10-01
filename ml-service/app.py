from __future__ import annotations

import os

from flask import Flask, jsonify, request

from prediction import PredictionValidationError, Predictor


def create_app(predictor: Predictor | None = None) -> Flask:
    app = Flask(__name__)
    model = predictor or Predictor()

    @app.get("/health")
    def health():
        if not model.is_ready():
            return (
                jsonify(
                    {
                        "status": "not_ready",
                        "message": "The trained model is unavailable. Run python train_model.py.",
                    }
                ),
                503,
            )

        return jsonify({"status": "ok", **model.metadata()})

    @app.post("/predict")
    def predict():
        try:
            result = model.predict(request.get_json(silent=True))
        except PredictionValidationError as exception:
            return jsonify({"message": str(exception), "errors": exception.errors}), 422
        except FileNotFoundError as exception:
            return jsonify({"message": str(exception)}), 503

        return jsonify(result)

    return app


app = create_app()

if __name__ == "__main__":
    app.run(
        host=os.getenv("HOST", "0.0.0.0"),
        port=int(os.getenv("PORT", "5001")),
        debug=os.getenv("FLASK_DEBUG", "false").lower() == "true",
    )
