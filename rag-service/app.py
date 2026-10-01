from __future__ import annotations

import os

from flask import Flask, jsonify, request
from werkzeug.exceptions import RequestEntityTooLarge

from extraction import PdfExtractionError, extract_pdf


def create_app() -> Flask:
    app = Flask(__name__)
    max_pdf_mb = max(1, int(os.getenv("MAX_PDF_SIZE_MB", "10")))
    app.config["MAX_CONTENT_LENGTH"] = max_pdf_mb * 1024 * 1024 + 64 * 1024

    @app.get("/health")
    def health():
        return jsonify({"status": "ok", "service": "edupulse-rag", "phase": 9})

    @app.post("/extract")
    def extract():
        uploaded = request.files.get("file")
        if uploaded is None or not uploaded.filename:
            return jsonify({"message": "A PDF file is required."}), 422

        if uploaded.mimetype not in {"application/pdf", "application/x-pdf"}:
            return jsonify({"message": "Only PDF documents are accepted."}), 422

        try:
            result = extract_pdf(uploaded.read())
        except PdfExtractionError as exception:
            return jsonify({"message": str(exception)}), 422

        return jsonify(result)

    @app.errorhandler(RequestEntityTooLarge)
    def too_large(_exception):
        return jsonify({"message": f"The PDF exceeds the {max_pdf_mb} MB limit."}), 413

    return app


app = create_app()

if __name__ == "__main__":
    app.run(
        host=os.getenv("HOST", "0.0.0.0"),
        port=int(os.getenv("PORT", "5002")),
        debug=os.getenv("FLASK_DEBUG", "false").lower() == "true",
    )
