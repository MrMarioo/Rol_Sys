import json
import math
from dataclasses import dataclass, field
from typing import Any

SENSITIVITY_LEVELS = ("low", "medium", "high")
DEFAULT_SENSITIVITY = "medium"
DEFAULT_PREDICTION_DAYS = 7
MIN_PREDICTION_DAYS = 1
MAX_PREDICTION_DAYS = 30

VALUE_KEYS_BY_TYPE = {
    "ndvi": "ndvi_values",
    "soil_moisture": "moisture_values",
}


class ValidationError(Exception):
    def __init__(self, errors: dict[str, str]):
        super().__init__("Invalid request payload")
        self.errors = errors


@dataclass(frozen=True)
class AnalysisRequest:
    field_data: list[dict[str, Any]] = field(default_factory=list)
    field_info: dict[str, Any] = field(default_factory=dict)
    sensitivity: str = DEFAULT_SENSITIVITY
    include_growth_prediction: bool = False
    prediction_days: int = DEFAULT_PREDICTION_DAYS


def parse_analysis_request(payload: Any) -> AnalysisRequest:
    if not isinstance(payload, dict):
        raise ValidationError({"body": "Request body must be a JSON object."})

    errors: dict[str, str] = {}
    field_data = _parse_field_data(payload.get("field_data"), errors)
    field_info = _parse_field_info(payload.get("field_info"), errors)
    parameters = payload.get("parameters")

    if parameters is None:
        parameters = {}
    elif not isinstance(parameters, dict):
        errors["parameters"] = "Must be an object."
        parameters = {}

    sensitivity = _parse_sensitivity(parameters.get("sensitivity"), errors)
    include_growth_prediction = _parse_bool(
        parameters.get("include_growth_prediction"), "parameters.include_growth_prediction", errors
    )
    prediction_days = _parse_prediction_days(parameters.get("prediction_days"), errors)

    if errors:
        raise ValidationError(errors)

    return AnalysisRequest(
        field_data=field_data,
        field_info=field_info,
        sensitivity=sensitivity,
        include_growth_prediction=include_growth_prediction,
        prediction_days=prediction_days,
    )


def _parse_field_data(raw: Any, errors: dict[str, str]) -> list[dict[str, Any]]:
    if raw is None:
        return []
    if not isinstance(raw, list):
        errors["field_data"] = "Must be a list."
        return []

    items = []
    for index, item in enumerate(raw):
        path = f"field_data.{index}"
        if not isinstance(item, dict):
            errors[path] = "Must be an object."
            continue

        data_type = item.get("data_type")
        if data_type is not None and not isinstance(data_type, str):
            errors[f"{path}.data_type"] = "Must be a string."
            continue

        values_key = VALUE_KEYS_BY_TYPE.get(data_type)
        if values_key is None:
            items.append(item)
            continue

        data = _decode_data(item.get("data"))
        if data is None:
            errors[f"{path}.data"] = "Must be an object."
            continue

        values = data.get(values_key, [])
        if not _is_number_list(values):
            errors[f"{path}.data.{values_key}"] = "Must be a list of numbers."
            continue

        items.append({**item, "data": {**data, values_key: values}})

    return items


def _parse_field_info(raw: Any, errors: dict[str, str]) -> dict[str, Any]:
    if raw is None:
        return {}
    if not isinstance(raw, dict):
        errors["field_info"] = "Must be an object."
        return {}

    info = dict(raw)
    size = info.get("size")
    if size is not None:
        parsed_size = _to_float(size)
        if parsed_size is None or parsed_size < 0:
            errors["field_info.size"] = "Must be a non-negative number."
        else:
            info["size"] = parsed_size

    planting_date = info.get("planting_date")
    if planting_date is not None and not isinstance(planting_date, str):
        errors["field_info.planting_date"] = "Must be a date string."

    return info


def _parse_sensitivity(raw: Any, errors: dict[str, str]) -> str:
    if raw is None:
        return DEFAULT_SENSITIVITY
    if raw not in SENSITIVITY_LEVELS:
        errors["parameters.sensitivity"] = f"Must be one of: {', '.join(SENSITIVITY_LEVELS)}."
        return DEFAULT_SENSITIVITY
    return raw


def _parse_bool(raw: Any, path: str, errors: dict[str, str]) -> bool:
    if raw is None:
        return False
    if isinstance(raw, bool):
        return raw
    if isinstance(raw, int) and raw in (0, 1):
        return bool(raw)
    errors[path] = "Must be a boolean."
    return False


def _parse_prediction_days(raw: Any, errors: dict[str, str]) -> int:
    if raw is None:
        return DEFAULT_PREDICTION_DAYS

    days = None
    if isinstance(raw, int) and not isinstance(raw, bool):
        days = raw
    elif isinstance(raw, str) and raw.strip().lstrip("-").isdigit():
        days = int(raw)

    if days is None:
        errors["parameters.prediction_days"] = "Must be an integer."
        return DEFAULT_PREDICTION_DAYS

    return max(MIN_PREDICTION_DAYS, min(MAX_PREDICTION_DAYS, days))


def _decode_data(raw: Any) -> dict[str, Any] | None:
    if raw is None:
        return {}
    if isinstance(raw, str):
        try:
            raw = json.loads(raw)
        except ValueError:
            return None
    return raw if isinstance(raw, dict) else None


def _is_number_list(values: Any) -> bool:
    return isinstance(values, list) and all(_is_finite_number(value) for value in values)


def _is_finite_number(value: Any) -> bool:
    return isinstance(value, int | float) and not isinstance(value, bool) and math.isfinite(value)


def _to_float(value: Any) -> float | None:
    if isinstance(value, bool):
        return None
    try:
        parsed = float(value)
    except (TypeError, ValueError):
        return None
    return parsed if math.isfinite(parsed) else None
