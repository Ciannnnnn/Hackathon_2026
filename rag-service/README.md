# EduPulse AI RAG service

This Flask service validates text-based PDFs, extracts text with `pypdf`, and returns page-aware overlapping chunks for Laravel to persist. Phase 10 will add retrieval over those chunks.

## Run locally

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
python app.py
```

The service listens on `http://localhost:5002` by default.

Endpoints:

- `GET /health` - service readiness
- `POST /extract` - multipart request with a `file` PDF field

Only text-based, unencrypted PDFs are supported in Phase 9. Scanned image documents require OCR and return a clear validation error.
