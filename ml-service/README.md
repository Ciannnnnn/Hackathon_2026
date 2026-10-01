# EduPulse AI ML service

This Flask service predicts academic support levels from seven academic indicators using a trained `RandomForestClassifier`.

## Local setup

```powershell
cd ml-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -r requirements.txt
python train_model.py
python app.py
```

The service runs on `http://localhost:5001` by default.

## API

- `GET /health` verifies that the trained model can be loaded and reports its version and test accuracy.
- `POST /predict` validates all seven features and returns `LOW`, `MODERATE`, or `HIGH`, a confidence score, class probabilities, and the model version.

Example request:

```json
{
  "attendance_rate": 68,
  "quiz_average": 59,
  "assignment_average": 71,
  "late_submissions": 4,
  "missing_submissions": 2,
  "activity_score": 42,
  "performance_trend": -12
}
```

Generated CSV data and model binaries are ignored by Git. Run `python train_model.py` on a new checkout or during deployment before starting Gunicorn.

Run the API tests with:

```powershell
python -m unittest discover -s tests -v
```
