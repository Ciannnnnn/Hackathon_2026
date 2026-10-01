FEATURE_NAMES = [
    "attendance_rate",
    "quiz_average",
    "assignment_average",
    "late_submissions",
    "missing_submissions",
    "activity_score",
    "performance_trend",
]

RATE_FEATURES = {
    "attendance_rate",
    "quiz_average",
    "assignment_average",
    "activity_score",
}

COUNT_FEATURES = {"late_submissions", "missing_submissions"}

SUPPORT_LEVELS = {0: "LOW", 1: "MODERATE", 2: "HIGH"}
