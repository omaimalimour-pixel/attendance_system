#!/usr/bin/env python3
"""Train ChronoX's absence-risk model and save the next predictions in MySQL."""

from __future__ import annotations

import argparse
import os
import sys
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any

try:
    import joblib
    import mysql.connector
    import pandas as pd
    from sklearn.compose import ColumnTransformer
    from sklearn.ensemble import RandomForestClassifier
    from sklearn.metrics import accuracy_score, f1_score
    from sklearn.pipeline import Pipeline
    from sklearn.preprocessing import OneHotEncoder
except ImportError as exc:
    raise SystemExit(
        "Missing Python packages. Run: pip install -r ai/requirements.txt"
    ) from exc


AI_DIR = Path(__file__).resolve().parent
MODEL_PATH = AI_DIR / "model.joblib"
HOLIDAYS_PATH = AI_DIR / "holidays.txt"
MODEL_VERSION = "random-forest-v1"
MIN_HISTORY_DAYS = max(5, int(os.getenv("CHRONOX_AI_MIN_HISTORY_DAYS", "20")))
MAX_LOOKBACK_DAYS = max(90, int(os.getenv("CHRONOX_AI_MAX_LOOKBACK_DAYS", "400")))

NUMERIC_FEATURES = [
    "weekday",
    "month",
    "absence_rate_7",
    "absence_rate_30",
    "late_rate_7",
    "late_rate_30",
    "present_streak",
    "absence_streak",
]
CATEGORICAL_FEATURES = ["department_id"]
MODEL_FEATURES = CATEGORICAL_FEATURES + NUMERIC_FEATURES


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Predict the risk of employee absence for the next workday."
    )
    parser.add_argument(
        "--prediction-date",
        help="Target date in YYYY-MM-DD format (default: next workday).",
    )
    return parser.parse_args()


def database_connection():
    return mysql.connector.connect(
        host=os.getenv("CHRONOX_DB_HOST", "127.0.0.1"),
        port=int(os.getenv("CHRONOX_DB_PORT", "3306")),
        user=os.getenv("CHRONOX_DB_USER", "root"),
        password=os.getenv("CHRONOX_DB_PASS", ""),
        database=os.getenv("CHRONOX_DB_NAME", "clocking"),
        charset="utf8mb4",
    )


def load_holidays() -> set[date]:
    holidays: set[date] = set()
    if not HOLIDAYS_PATH.exists():
        return holidays

    for line_number, raw_line in enumerate(
        HOLIDAYS_PATH.read_text(encoding="utf-8").splitlines(), start=1
    ):
        value = raw_line.strip()
        if not value or value.startswith("#"):
            continue
        try:
            holidays.add(datetime.strptime(value, "%Y-%m-%d").date())
        except ValueError as exc:
            raise SystemExit(
                f"Invalid date in ai/holidays.txt at line {line_number}: {value}"
            ) from exc
    return holidays


def is_workday(day: date, holidays: set[date]) -> bool:
    return day.weekday() < 5 and day not in holidays


def next_workday(day: date, holidays: set[date]) -> date:
    candidate = day + timedelta(days=1)
    while not is_workday(candidate, holidays):
        candidate += timedelta(days=1)
    return candidate


def workdays_between(start: date, end: date, holidays: set[date]) -> list[date]:
    if end < start:
        return []
    days: list[date] = []
    current = start
    while current <= end:
        if is_workday(current, holidays):
            days.append(current)
        current += timedelta(days=1)
    return days


def as_date(value: Any) -> date:
    if isinstance(value, datetime):
        return value.date()
    if isinstance(value, date):
        return value
    return datetime.strptime(str(value), "%Y-%m-%d").date()


def as_time(value: Any):
    if isinstance(value, timedelta):
        total_seconds = int(value.total_seconds())
        return (datetime.min + timedelta(seconds=total_seconds)).time()
    if hasattr(value, "hour"):
        return value
    return datetime.strptime(str(value), "%H:%M:%S").time()


def fetch_rows(connection, sql: str, params: tuple[Any, ...] = ()) -> list[dict[str, Any]]:
    cursor = connection.cursor(dictionary=True)
    try:
        cursor.execute(sql, params)
        return list(cursor.fetchall())
    finally:
        cursor.close()


def ensure_predictions_table(connection) -> None:
    cursor = connection.cursor()
    try:
        cursor.execute(
            """
            CREATE TABLE IF NOT EXISTS absence_predictions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                prediction_date DATE NOT NULL,
                probability DECIMAL(5,2) NOT NULL,
                risk_level ENUM('low','medium','high') NOT NULL,
                reason VARCHAR(255) NULL,
                history_days INT NOT NULL DEFAULT 0,
                model_version VARCHAR(60) NOT NULL,
                generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ai_prediction_date (prediction_date),
                INDEX idx_ai_risk (risk_level),
                UNIQUE KEY uniq_ai_user_date (user_id, prediction_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            """
        )
        connection.commit()
    finally:
        cursor.close()


def load_source_data(connection, history_end: date):
    employees = fetch_rows(
        connection,
        """
        SELECT user_id, department_id, created_at
        FROM employees
        WHERE status = 'active'
        ORDER BY user_id
        """,
    )
    if not employees:
        raise SystemExit("No active employee was found in the employees table.")

    earliest_allowed = history_end - timedelta(days=MAX_LOOKBACK_DAYS)
    attendance = fetch_rows(
        connection,
        """
        SELECT user_id, date, MIN(time) AS first_punch
        FROM attendance
        WHERE date BETWEEN %s AND %s
        GROUP BY user_id, date
        ORDER BY date, user_id
        """,
        (earliest_allowed, history_end),
    )
    if not attendance:
        raise SystemExit(
            "No completed attendance history was found. Sync the devices before running the AI module."
        )

    setting_rows = fetch_rows(
        connection,
        "SELECT `value` FROM settings WHERE `key` = 'work_start' LIMIT 1",
    )
    work_start_value = (
        setting_rows[0]["value"]
        if setting_rows
        else os.getenv("CHRONOX_AI_WORK_START", "09:00:00")
    )
    work_start = as_time(work_start_value)
    return employees, attendance, work_start


def attendance_index(attendance, work_start) -> dict[int, dict[date, bool]]:
    indexed: dict[int, dict[date, bool]] = {}
    for row in attendance:
        user_id = int(row["user_id"])
        day = as_date(row["date"])
        first_punch = as_time(row["first_punch"])
        indexed.setdefault(user_id, {})[day] = first_punch > work_start
    return indexed


def streak(values: list[int], expected: int) -> int:
    total = 0
    for value in reversed(values):
        if value != expected:
            break
        total += 1
    return total


def feature_row(
    employee: dict[str, Any],
    target_day: date,
    previous_days: list[date],
    punches: dict[int, dict[date, bool]],
) -> dict[str, Any]:
    user_id = int(employee["user_id"])
    user_punches = punches.get(user_id, {})

    def window_values(size: int) -> tuple[list[int], list[int]]:
        selected = previous_days[-size:]
        presence = [1 if day in user_punches else 0 for day in selected]
        late = [1 if user_punches.get(day, False) else 0 for day in selected]
        return presence, late

    presence_7, late_7 = window_values(7)
    presence_30, late_30 = window_values(30)
    present_7 = max(1, sum(presence_7))
    present_30 = max(1, sum(presence_30))

    return {
        "user_id": user_id,
        "sample_date": target_day,
        "department_id": str(employee.get("department_id") or "unassigned"),
        "weekday": target_day.weekday(),
        "month": target_day.month,
        "absence_rate_7": 1.0 - (sum(presence_7) / max(1, len(presence_7))),
        "absence_rate_30": 1.0 - (sum(presence_30) / max(1, len(presence_30))),
        "late_rate_7": sum(late_7) / present_7,
        "late_rate_30": sum(late_30) / present_30,
        "present_streak": streak(presence_30, 1),
        "absence_streak": streak(presence_30, 0),
        "history_days": len(previous_days),
    }


def employee_start(employee: dict[str, Any], calendar_start: date) -> date:
    created_at = employee.get("created_at")
    if not created_at:
        return calendar_start
    return max(calendar_start, as_date(created_at))


def build_training_data(
    employees,
    punches: dict[int, dict[date, bool]],
    calendar: list[date],
) -> pd.DataFrame:
    rows: list[dict[str, Any]] = []
    if not calendar:
        return pd.DataFrame()

    for employee in employees:
        start = employee_start(employee, calendar[0])
        eligible_days = [day for day in calendar if day >= start]
        user_punches = punches.get(int(employee["user_id"]), {})
        for index, target_day in enumerate(eligible_days):
            previous_days = eligible_days[:index]
            if len(previous_days) < MIN_HISTORY_DAYS:
                continue
            row = feature_row(employee, target_day, previous_days, punches)
            row["absent"] = 0 if target_day in user_punches else 1
            rows.append(row)
    return pd.DataFrame(rows)


def build_prediction_data(
    employees,
    punches: dict[int, dict[date, bool]],
    calendar: list[date],
    prediction_day: date,
) -> pd.DataFrame:
    rows: list[dict[str, Any]] = []
    if not calendar:
        return pd.DataFrame()

    for employee in employees:
        start = employee_start(employee, calendar[0])
        previous_days = [day for day in calendar if day >= start and day < prediction_day]
        if len(previous_days) < MIN_HISTORY_DAYS:
            continue
        rows.append(feature_row(employee, prediction_day, previous_days, punches))
    return pd.DataFrame(rows)


def make_pipeline() -> Pipeline:
    preprocessing = ColumnTransformer(
        transformers=[
            (
                "department",
                OneHotEncoder(handle_unknown="ignore"),
                CATEGORICAL_FEATURES,
            )
        ],
        remainder="passthrough",
    )
    classifier = RandomForestClassifier(
        n_estimators=250,
        min_samples_leaf=2,
        class_weight="balanced_subsample",
        random_state=42,
        n_jobs=-1,
    )
    return Pipeline(
        steps=[("preprocessing", preprocessing), ("classifier", classifier)]
    )


def evaluate_chronologically(data: pd.DataFrame) -> dict[str, float] | None:
    unique_dates = sorted(data["sample_date"].unique())
    if len(unique_dates) < 10:
        return None
    split_index = max(1, int(len(unique_dates) * 0.8))
    test_dates = set(unique_dates[split_index:])
    train = data[~data["sample_date"].isin(test_dates)]
    test = data[data["sample_date"].isin(test_dates)]
    if train.empty or test.empty or train["absent"].nunique() < 2:
        return None

    validation_model = make_pipeline()
    validation_model.fit(train[MODEL_FEATURES], train["absent"])
    predicted = validation_model.predict(test[MODEL_FEATURES])
    return {
        "accuracy": float(accuracy_score(test["absent"], predicted)),
        "f1": float(f1_score(test["absent"], predicted, zero_division=0)),
    }


def risk_level(probability_percent: float) -> str:
    if probability_percent >= 65:
        return "high"
    if probability_percent >= 35:
        return "medium"
    return "low"


def main_factor(row: pd.Series) -> str:
    if row["absence_streak"] >= 2:
        return "Several consecutive recent absences"
    if row["absence_rate_30"] >= 0.30:
        return "High recent absence rate"
    if row["late_rate_30"] >= 0.30:
        return "Frequent recent late arrivals"
    if row["absence_rate_7"] > row["absence_rate_30"] + 0.15:
        return "Recent attendance has declined"
    return "Pattern estimated from recent attendance history"


def save_predictions(connection, predictions: pd.DataFrame, prediction_day: date) -> None:
    sql = """
        INSERT INTO absence_predictions
            (user_id, prediction_date, probability, risk_level, reason,
             history_days, model_version, generated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
        ON DUPLICATE KEY UPDATE
            probability = VALUES(probability),
            risk_level = VALUES(risk_level),
            reason = VALUES(reason),
            history_days = VALUES(history_days),
            model_version = VALUES(model_version),
            generated_at = NOW()
    """
    values = [
        (
            int(row["user_id"]),
            prediction_day,
            round(float(row["probability"]), 2),
            row["risk_level"],
            row["reason"],
            int(min(30, row["history_days"])),
            MODEL_VERSION,
        )
        for _, row in predictions.iterrows()
    ]
    cursor = connection.cursor()
    try:
        cursor.executemany(sql, values)
        connection.commit()
    finally:
        cursor.close()


def main() -> int:
    args = parse_args()
    holidays = load_holidays()
    today = date.today()
    latest_complete_day = today - timedelta(days=1)

    if args.prediction_date:
        try:
            prediction_day = datetime.strptime(
                args.prediction_date, "%Y-%m-%d"
            ).date()
        except ValueError as exc:
            raise SystemExit("--prediction-date must use YYYY-MM-DD.") from exc
        if not is_workday(prediction_day, holidays):
            raise SystemExit("The selected prediction date is not a configured workday.")
        if prediction_day <= latest_complete_day:
            raise SystemExit("The prediction date must be today or a future workday.")
    else:
        prediction_day = next_workday(today, holidays)

    connection = database_connection()
    try:
        ensure_predictions_table(connection)
        latest_rows = fetch_rows(
            connection,
            "SELECT MAX(date) AS latest_date FROM attendance WHERE date <= %s",
            (latest_complete_day,),
        )
        latest_attendance = latest_rows[0]["latest_date"] if latest_rows else None
        if not latest_attendance:
            raise SystemExit(
                "No completed attendance history was found. Sync the devices before running the AI module."
            )
        history_end = min(latest_complete_day, as_date(latest_attendance))
        if (latest_complete_day - history_end).days > 3:
            print(
                f"Warning: the latest attendance date is {history_end.isoformat()}. "
                "Sync the devices for fresher predictions.",
                file=sys.stderr,
            )
        employees, attendance, work_start = load_source_data(connection, history_end)
        punches = attendance_index(attendance, work_start)
        first_attendance_day = min(as_date(row["date"]) for row in attendance)
        calendar_start = max(
            first_attendance_day, history_end - timedelta(days=MAX_LOOKBACK_DAYS)
        )
        calendar = workdays_between(calendar_start, history_end, holidays)

        training = build_training_data(employees, punches, calendar)
        if len(training) < 30 or training["absent"].nunique() < 2:
            raise SystemExit(
                "Not enough varied history to train the model. ChronoX needs at least "
                f"{MIN_HISTORY_DAYS} completed workdays per employee and both present and absent examples."
            )
        class_counts = training["absent"].value_counts()
        if class_counts.min() < 5:
            raise SystemExit(
                "The attendance history contains too few examples of one class. "
                "At least 5 present and 5 absent training examples are required."
            )

        future = build_prediction_data(employees, punches, calendar, prediction_day)
        if future.empty:
            raise SystemExit(
                "No employee has enough history for a prediction yet. Add or sync more attendance data."
            )

        metrics = evaluate_chronologically(training)
        model = make_pipeline()
        model.fit(training[MODEL_FEATURES], training["absent"])
        class_index = list(model.named_steps["classifier"].classes_).index(1)
        probabilities = model.predict_proba(future[MODEL_FEATURES])[:, class_index] * 100

        future = future.copy()
        future["probability"] = probabilities
        future["risk_level"] = future["probability"].map(risk_level)
        future["reason"] = future.apply(main_factor, axis=1)

        joblib.dump(
            {
                "pipeline": model,
                "model_version": MODEL_VERSION,
                "trained_at": datetime.now().isoformat(timespec="seconds"),
                "training_samples": len(training),
                "metrics": metrics,
            },
            MODEL_PATH,
        )
        save_predictions(connection, future, prediction_day)

        print(f"AI prediction date: {prediction_day.isoformat()}")
        print(f"Training samples: {len(training)}")
        print(f"Employees predicted: {len(future)}")
        if metrics:
            print(
                "Chronological validation: "
                f"accuracy={metrics['accuracy']:.2%}, f1={metrics['f1']:.2%}"
            )
        else:
            print("Chronological validation: not enough distinct dates for a stable test.")
        print("Predictions saved in MySQL table: absence_predictions")
        return 0
    finally:
        connection.close()


if __name__ == "__main__":
    try:
        sys.exit(main())
    except mysql.connector.Error as exc:
        raise SystemExit(f"MySQL error: {exc}") from exc
