from __future__ import annotations

from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report, confusion_matrix
from sklearn.model_selection import train_test_split

from features import FEATURE_NAMES, SUPPORT_LEVELS

BASE_DIR = Path(__file__).resolve().parent
DATA_PATH = BASE_DIR / "data" / "training_data.csv"
MODEL_PATH = BASE_DIR / "model" / "student_support_model.pkl"
MODEL_VERSION = "random-forest-v1"
RANDOM_SEED = 20261001


def generate_training_data(rows: int = 4_000) -> pd.DataFrame:
    """Create deterministic, correlated academic data for the hackathon model."""
    rng = np.random.default_rng(RANDOM_SEED)
    engagement = rng.beta(4.0, 2.2, rows)

    attendance = np.clip(48 + engagement * 55 + rng.normal(0, 7, rows), 35, 100)
    activity = np.clip(30 + engagement * 68 + rng.normal(0, 9, rows), 15, 100)
    quiz = np.clip(35 + engagement * 62 + rng.normal(0, 10, rows), 20, 100)
    assignment = np.clip(40 + engagement * 58 + rng.normal(0, 9, rows), 25, 100)
    late = np.clip(np.rint((1 - engagement) * 7 + rng.normal(0, 1, rows)), 0, 12).astype(int)
    missing = np.clip(np.rint((1 - engagement) * 5 + rng.normal(0, 0.8, rows)), 0, 8).astype(int)
    trend = np.clip((engagement - 0.58) * 32 + rng.normal(0, 6, rows), -30, 20)

    risk_score = (
        (100 - attendance) * 0.18
        + (100 - quiz) * 0.24
        + (100 - assignment) * 0.20
        + (100 - activity) * 0.16
        + late * 1.7
        + missing * 4.2
        + np.maximum(-trend, 0) * 0.65
        + rng.normal(0, 2.2, rows)
    )
    support_level = np.select([risk_score >= 50, risk_score >= 29], [2, 1], default=0)

    return pd.DataFrame(
        {
            "attendance_rate": np.round(attendance, 2),
            "quiz_average": np.round(quiz, 2),
            "assignment_average": np.round(assignment, 2),
            "late_submissions": late,
            "missing_submissions": missing,
            "activity_score": np.round(activity, 2),
            "performance_trend": np.round(trend, 2),
            "support_level": support_level.astype(int),
        }
    )


def train() -> dict[str, object]:
    DATA_PATH.parent.mkdir(parents=True, exist_ok=True)
    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)

    data = generate_training_data()
    data.to_csv(DATA_PATH, index=False)

    x_train, x_test, y_train, y_test = train_test_split(
        data[FEATURE_NAMES],
        data["support_level"],
        test_size=0.2,
        random_state=RANDOM_SEED,
        stratify=data["support_level"],
    )

    model = RandomForestClassifier(
        n_estimators=250,
        max_depth=14,
        min_samples_leaf=2,
        class_weight="balanced",
        random_state=RANDOM_SEED,
        n_jobs=-1,
    )
    model.fit(x_train, y_train)
    predictions = model.predict(x_test)
    accuracy = float(model.score(x_test, y_test))

    bundle = {
        "model": model,
        "feature_names": FEATURE_NAMES,
        "model_version": MODEL_VERSION,
        "support_levels": SUPPORT_LEVELS,
        "accuracy": accuracy,
    }
    joblib.dump(bundle, MODEL_PATH)

    print(f"Training rows: {len(x_train)} | Test rows: {len(x_test)}")
    print(f"Accuracy: {accuracy:.4f}")
    print("Confusion matrix:")
    print(confusion_matrix(y_test, predictions))
    print("Classification report:")
    print(
        classification_report(
            y_test,
            predictions,
            labels=[0, 1, 2],
            target_names=[SUPPORT_LEVELS[index] for index in [0, 1, 2]],
            zero_division=0,
        )
    )
    print(f"Saved model: {MODEL_PATH}")

    return bundle


if __name__ == "__main__":
    train()
