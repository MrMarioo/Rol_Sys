import json
import logging
from datetime import datetime, timedelta
from pathlib import Path

import joblib
import numpy as np
import pandas as pd

logger = logging.getLogger(__name__)

FEATURE_COLUMNS = [
    "avg_ndvi",
    "min_ndvi",
    "max_ndvi",
    "avg_moisture",
    "min_moisture",
    "max_moisture",
    "avg_temperature",
    "soil_temperature",
    "rainfall",
    "sunshine_hours",
    "days_since_planting",
    "growth_stage_encoded",
    "fertilizer_nitrogen",
    "fertilizer_phosphorus",
    "fertilizer_potassium",
    "field_size",
    "plant_height",
    "plant_density",
]

GROWTH_STAGE_NAMES = {
    0: "Germination",
    1: "Early Vegetative",
    2: "Vegetative Growth",
    3: "Flowering",
    4: "Grain Filling",
    5: "Maturity",
}
GROWTH_STAGE_DAY_LIMITS = (14, 30, 60, 90, 110)
HEIGHT_BY_DAY_LIMIT = ((14, 5), (30, 15), (60, 45), (90, 80))
MATURE_HEIGHT_CM = 90

RECENT_SURVEY_WINDOW = 7
RECENT_WEATHER_WINDOW = 3

DEFAULT_NDVI = {"avg_ndvi": 0.5, "min_ndvi": 0.3, "max_ndvi": 0.7}
DEFAULT_MOISTURE = {"avg_moisture": 0.45, "min_moisture": 0.25, "max_moisture": 0.65}
DEFAULT_AIR_TEMPERATURE = 20
DEFAULT_SOIL_TEMPERATURE = 16
DEFAULT_SUNSHINE_HOURS = 8
DEFAULT_DAYS_SINCE_PLANTING = 50
DEFAULT_FIELD_SIZE_HA = 5.5
DEFAULT_PLANT_HEIGHT_CM = 50
DEFAULT_PLANT_DENSITY = 225

NDVI_DAILY_GAIN = 0.002
NDVI_DAILY_LOSS = -0.005
NDVI_GROWTH_STAGE_LIMIT = 3
NDVI_RANGE = (0.1, 0.95)
MOISTURE_DAILY_GAIN = 0.02
MOISTURE_DAILY_LOSS = -0.01
MOISTURE_RANGE = (0.1, 0.9)
WET_RAINFALL_MM = 5
MIN_CURRENT_BIOMASS = 100

MAX_CONFIDENCE = 0.95
MIN_CONFIDENCE = 0.4
CONFIDENCE_DECAY_PER_DAY = 0.02

FERTILIZER_KEYS = {
    "nitrogen": "fertilizer_nitrogen",
    "n": "fertilizer_nitrogen",
    "phosphorus": "fertilizer_phosphorus",
    "p": "fertilizer_phosphorus",
    "potassium": "fertilizer_potassium",
    "k": "fertilizer_potassium",
}

PRIORITY_ORDER = {"high": 3, "medium": 2, "low": 1}
MAX_RECOMMENDATIONS = 8


class ModelNotAvailableError(RuntimeError):
    pass


class PlantGrowthPredictor:
    def __init__(self, model_path):
        self.model_path = Path(model_path)
        self.model = None
        self.scaler = None
        self.feature_columns = list(FEATURE_COLUMNS)
        self.training_date = None
        self.model_version = None
        self.load_model()

    def load_model(self):
        if not self.model_path.is_file():
            raise ModelNotAvailableError(
                f"Growth model not found at {self.model_path}. Run `python train_growth_model.py` to create it."
            )

        model_data = joblib.load(self.model_path)
        self.model = model_data["model"]
        if hasattr(self.model, "n_jobs"):
            self.model.n_jobs = 1
        self.scaler = model_data["scaler"]
        self.feature_columns = model_data["feature_columns"]
        self.training_date = model_data.get("training_date", "unknown")
        self.model_version = model_data.get("model_version", "1.0")
        logger.info("Growth model loaded: %s trained on %s", type(self.model).__name__, self.training_date)

    def predict_growth(self, field_data, field_info, days_ahead=7):
        current_features = self.prepare_features(field_data, field_info)
        current_biomass = max(MIN_CURRENT_BIOMASS, self._predict_biomass(current_features))

        predictions = []
        future_features = dict(current_features)

        for day in range(1, days_ahead + 1):
            future_features = self._advance_one_day(future_features, current_features, day)
            predicted_biomass = self._predict_biomass(future_features)
            previous_biomass = predictions[-1]["predicted_biomass"] if predictions else current_biomass

            predictions.append(
                {
                    "day": day,
                    "date": (datetime.now() + timedelta(days=day)).strftime("%Y-%m-%d"),
                    "predicted_biomass": float(max(current_biomass, predicted_biomass)),
                    "growth_rate": float(predicted_biomass - previous_biomass),
                    "confidence": self._calculate_confidence(current_features, day),
                    "growth_stage": GROWTH_STAGE_NAMES.get(future_features["growth_stage_encoded"], "Unknown"),
                    "estimated_ndvi": float(future_features["avg_ndvi"]),
                    "estimated_moisture": float(future_features["avg_moisture"]),
                }
            )

        return {
            "current_status": self._assess_current_status(current_features),
            "current_biomass": float(current_biomass),
            "predictions": predictions,
            "recommendations": self._generate_growth_recommendations(predictions, current_features),
            "summary": self._generate_prediction_summary(predictions),
            "model_info": {
                "model_type": type(self.model).__name__,
                "features_used": len(self.feature_columns),
                "training_date": self.training_date,
                "version": self.model_version,
            },
        }

    def prepare_features(self, field_data, field_info):
        by_type = {}
        for item in sorted(field_data, key=lambda entry: str(entry.get("collection_date") or "")):
            by_type.setdefault(item.get("data_type"), []).append(_as_dict(item.get("data")))

        features = {}
        features.update(_value_stats(by_type.get("ndvi", []), "ndvi_values", "ndvi", DEFAULT_NDVI))
        features.update(_value_stats(by_type.get("soil_moisture", []), "moisture_values", "moisture", DEFAULT_MOISTURE))

        weather = by_type.get("weather", [])[-RECENT_WEATHER_WINDOW:]
        temperatures = [entry.get("temperature_avg", DEFAULT_AIR_TEMPERATURE) for entry in weather]
        features["avg_temperature"] = float(np.mean(temperatures)) if temperatures else DEFAULT_AIR_TEMPERATURE
        features["rainfall"] = float(sum(entry.get("rainfall", 0) for entry in weather))

        soil_temperature = by_type.get("soil_temperature", [])
        features["soil_temperature"] = (
            soil_temperature[-1].get("avg_temperature", DEFAULT_SOIL_TEMPERATURE)
            if soil_temperature
            else DEFAULT_SOIL_TEMPERATURE
        )

        sunlight = by_type.get("sunlight", [])[-RECENT_WEATHER_WINDOW:]
        sun_hours = [entry.get("sunshine_hours", DEFAULT_SUNSHINE_HOURS) for entry in sunlight]
        features["sunshine_hours"] = float(np.mean(sun_hours)) if sun_hours else DEFAULT_SUNSHINE_HOURS

        features["days_since_planting"] = _days_since_planting(field_info.get("planting_date"))
        features["growth_stage_encoded"] = _growth_stage(features["days_since_planting"])

        features["fertilizer_nitrogen"] = 0
        features["fertilizer_phosphorus"] = 0
        features["fertilizer_potassium"] = 0
        for entry in by_type.get("fertilizer_application", []):
            key = FERTILIZER_KEYS.get(str(entry.get("fertilizer_type", "")).strip().lower())
            if key:
                features[key] += entry.get("amount_per_hectare", 0)

        features["field_size"] = field_info.get("size") or DEFAULT_FIELD_SIZE_HA

        biomass = by_type.get("biomass_measurement", [])
        if biomass:
            features["plant_height"] = biomass[-1].get("plant_height_cm", DEFAULT_PLANT_HEIGHT_CM)
            features["plant_density"] = biomass[-1].get("plant_density_per_m2", DEFAULT_PLANT_DENSITY)
        else:
            features["plant_height"] = _estimate_height(features["days_since_planting"])
            features["plant_density"] = DEFAULT_PLANT_DENSITY

        return features

    def _advance_one_day(self, previous, current, day):
        future = dict(previous)
        future["days_since_planting"] = current["days_since_planting"] + day
        future["growth_stage_encoded"] = _growth_stage(future["days_since_planting"])

        ndvi_change = NDVI_DAILY_GAIN if future["growth_stage_encoded"] <= NDVI_GROWTH_STAGE_LIMIT else NDVI_DAILY_LOSS
        future["avg_ndvi"] = float(np.clip(previous["avg_ndvi"] + ndvi_change, *NDVI_RANGE))

        moisture_change = MOISTURE_DAILY_GAIN if future["rainfall"] > WET_RAINFALL_MM else MOISTURE_DAILY_LOSS
        future["avg_moisture"] = float(np.clip(previous["avg_moisture"] + moisture_change, *MOISTURE_RANGE))
        return future

    def _predict_biomass(self, features):
        frame = pd.DataFrame([[features[column] for column in self.feature_columns]], columns=self.feature_columns)
        return float(self.model.predict(self.scaler.transform(frame.astype(float)))[0])

    def _assess_current_status(self, features):
        status = "normal"
        issues = []
        warnings = []

        avg_ndvi = features["avg_ndvi"]
        if avg_ndvi < 0.25:
            status = "critical"
            issues.append(f"Critically low vegetation health (NDVI: {avg_ndvi:.2f})")
        elif avg_ndvi < 0.4:
            status = "poor"
            issues.append(f"Low vegetation health (NDVI: {avg_ndvi:.2f})")
        elif avg_ndvi < 0.5:
            warnings.append(f"Below average vegetation health (NDVI: {avg_ndvi:.2f})")
        elif avg_ndvi > 0.8:
            status = "excellent"

        avg_moisture = features["avg_moisture"]
        if avg_moisture < 0.2:
            issues.append(f"Critically low soil moisture ({avg_moisture:.2f})")
            if status not in ("critical", "poor"):
                status = "poor"
        elif avg_moisture < 0.3:
            warnings.append(f"Low soil moisture ({avg_moisture:.2f})")
        elif avg_moisture > 0.8:
            warnings.append(f"Very high soil moisture ({avg_moisture:.2f}) - risk of waterlogging")

        avg_temp = features["avg_temperature"]
        if avg_temp > 32:
            warnings.append(f"High temperature stress risk ({avg_temp:.1f}°C)")
        elif avg_temp < 8:
            warnings.append(f"Low temperature may slow growth ({avg_temp:.1f}°C)")

        moisture_score = 1.0 if 0.3 <= avg_moisture <= 0.7 else max(0.3, 1.0 - abs(avg_moisture - 0.5))
        temperature_score = 1.0 if 15 <= avg_temp <= 25 else max(0.3, 1.0 - abs(avg_temp - 20) / 10)
        health_score = min(100, max(0, avg_ndvi * 40 + moisture_score * 30 + temperature_score * 20 + 1))

        return {
            "status": status,
            "growth_stage": GROWTH_STAGE_NAMES.get(features["growth_stage_encoded"], "Unknown"),
            "days_since_planting": features["days_since_planting"],
            "issues": issues,
            "warnings": warnings,
            "health_score": round(float(health_score), 1),
            "key_metrics": {
                "ndvi": round(float(avg_ndvi), 3),
                "soil_moisture": round(float(avg_moisture), 3),
                "temperature": round(float(avg_temp), 1),
            },
        }

    def _generate_growth_recommendations(self, predictions, features):
        recommendations = []

        if len(predictions) >= 3:
            avg_growth_rate = float(np.mean([p["growth_rate"] for p in predictions[:3]]))
            if avg_growth_rate < 30:
                recommendations.append(
                    _recommendation(
                        "urgent",
                        "high",
                        "growth",
                        "Investigate slow growth",
                        f"Very slow growth rate detected ({avg_growth_rate:.1f} kg/ha/day). "
                        "Normal rate: 60-120 kg/ha/day.",
                        3,
                        "High",
                    )
                )
            elif avg_growth_rate < 50:
                recommendations.append(
                    _recommendation(
                        "warning",
                        "medium",
                        "nutrition",
                        "Consider nitrogen fertilization",
                        f"Below-optimal growth rate ({avg_growth_rate:.1f} kg/ha/day). Consider nutrient analysis.",
                        7,
                        "Medium",
                    )
                )

        moisture = features["avg_moisture"]
        if moisture < 0.25:
            recommendations.append(
                _recommendation(
                    "urgent",
                    "high",
                    "irrigation",
                    "Immediate irrigation required",
                    f"Critically low soil moisture ({moisture:.2f}). Target range: 0.4-0.6.",
                    1,
                    "Critical",
                )
            )
        elif moisture < 0.35:
            recommendations.append(
                _recommendation(
                    "high",
                    "high",
                    "irrigation",
                    "Increase irrigation",
                    f"Low soil moisture ({moisture:.2f}). Increase watering frequency.",
                    2,
                    "High",
                )
            )
        elif moisture > 0.75:
            recommendations.append(
                _recommendation(
                    "caution",
                    "medium",
                    "irrigation",
                    "Reduce irrigation",
                    f"High soil moisture ({moisture:.2f}). Risk of root diseases and nutrient leaching.",
                    3,
                    "Medium",
                )
            )

        ndvi = features["avg_ndvi"]
        if ndvi < 0.3:
            recommendations.append(
                _recommendation(
                    "urgent",
                    "high",
                    "health",
                    "Comprehensive crop health assessment",
                    f"Very low NDVI ({ndvi:.2f}) indicates serious health issues. "
                    "Check for pests, diseases, nutrient deficiency.",
                    1,
                    "Critical",
                )
            )
        elif ndvi < 0.5:
            recommendations.append(
                _recommendation(
                    "warning",
                    "medium",
                    "health",
                    "Monitor crop condition closely",
                    f"Below-average NDVI ({ndvi:.2f}). Consider foliar feeding or pest inspection.",
                    5,
                    "Medium",
                )
            )

        stage = features["growth_stage_encoded"]
        if stage == 1 and features["fertilizer_nitrogen"] < 60:
            recommendations.append(
                _recommendation(
                    "scheduled",
                    "medium",
                    "nutrition",
                    "Apply nitrogen fertilizer",
                    "Optimal timing for nitrogen application during early vegetative growth "
                    "(80-120 kg/ha recommended).",
                    5,
                    "High",
                )
            )
        if stage == 2 and features["fertilizer_phosphorus"] < 40:
            recommendations.append(
                _recommendation(
                    "scheduled",
                    "medium",
                    "nutrition",
                    "Consider phosphorus application",
                    "Phosphorus supports root development and energy transfer during vegetative growth.",
                    10,
                    "Medium",
                )
            )
        if stage == 3 and features["fertilizer_potassium"] < 50:
            recommendations.append(
                _recommendation(
                    "scheduled",
                    "medium",
                    "nutrition",
                    "Apply potassium fertilizer",
                    "Potassium is crucial during flowering for fruit/grain development.",
                    7,
                    "High",
                )
            )

        temperature = features["avg_temperature"]
        if temperature > 30:
            recommendations.append(
                _recommendation(
                    "caution",
                    "medium",
                    "weather",
                    "Heat stress mitigation",
                    f"High temperatures ({temperature:.1f}°C) may cause stress. Ensure adequate water supply.",
                    2,
                    "Medium",
                )
            )

        if len(predictions) >= 5:
            future_ndvi = float(np.mean([p["estimated_ndvi"] for p in predictions[-3:]]))
            if future_ndvi < ndvi - 0.1:
                recommendations.append(
                    _recommendation(
                        "predictive",
                        "medium",
                        "health",
                        "Prepare for declining crop health",
                        "Model predicts declining NDVI. Consider preventive measures.",
                        5,
                        "Medium",
                    )
                )

        if all(rec["priority"] == "low" for rec in recommendations):
            recommendations.append(
                _recommendation(
                    "positive",
                    "low",
                    "general",
                    "Continue current management",
                    "Crop conditions are good. Maintain current practices and monitor regularly.",
                    14,
                    "Low",
                )
            )

        recommendations.sort(
            key=lambda rec: (PRIORITY_ORDER.get(rec["priority"], 0), -rec["urgency_days"]),
            reverse=True,
        )
        return recommendations[:MAX_RECOMMENDATIONS]

    def _generate_prediction_summary(self, predictions):
        if not predictions:
            return {}

        total_growth = predictions[-1]["predicted_biomass"] - predictions[0]["predicted_biomass"]
        peak_day = max(predictions, key=lambda p: p["growth_rate"])
        confidences = [p["confidence"] for p in predictions]

        return {
            "total_predicted_growth": round(total_growth, 1),
            "average_daily_growth": round(total_growth / len(predictions), 1),
            "peak_growth_day": peak_day["day"],
            "peak_growth_rate": round(peak_day["growth_rate"], 1),
            "final_biomass": round(predictions[-1]["predicted_biomass"], 1),
            "confidence_range": {
                "min": round(min(confidences), 2),
                "max": round(max(confidences), 2),
                "avg": round(float(np.mean(confidences)), 2),
            },
        }

    def _calculate_confidence(self, features, days_ahead):
        confidence = MAX_CONFIDENCE - CONFIDENCE_DECAY_PER_DAY * days_ahead
        if features["avg_ndvi"] < 0.2 or features["avg_ndvi"] > 0.9:
            confidence -= 0.1
        if features["avg_moisture"] < 0.1 or features["avg_moisture"] > 0.8:
            confidence -= 0.1
        if features["days_since_planting"] < 10 or features["days_since_planting"] > 120:
            confidence -= 0.05
        return max(MIN_CONFIDENCE, min(MAX_CONFIDENCE, confidence))


def _recommendation(rec_type, priority, category, action, description, urgency_days, impact):
    return {
        "type": rec_type,
        "priority": priority,
        "category": category,
        "action": action,
        "description": description,
        "urgency_days": urgency_days,
        "estimated_impact": impact,
        "source": "growth_prediction",
    }


def _as_dict(data):
    if isinstance(data, str):
        try:
            data = json.loads(data)
        except ValueError:
            return {}
    return data if isinstance(data, dict) else {}


def _value_stats(entries, values_key, suffix, defaults):
    values = [value for entry in entries[-RECENT_SURVEY_WINDOW:] for value in entry.get(values_key, [])]
    if not values:
        return dict(defaults)
    return {
        f"avg_{suffix}": float(np.mean(values)),
        f"min_{suffix}": float(np.min(values)),
        f"max_{suffix}": float(np.max(values)),
    }


def _days_since_planting(planting_date):
    if not isinstance(planting_date, str):
        return DEFAULT_DAYS_SINCE_PLANTING
    try:
        planted = datetime.strptime(planting_date[:10], "%Y-%m-%d")
    except ValueError:
        return DEFAULT_DAYS_SINCE_PLANTING
    return max(1, (datetime.now() - planted).days)


def _growth_stage(days_since_planting):
    for stage, limit in enumerate(GROWTH_STAGE_DAY_LIMITS):
        if days_since_planting < limit:
            return stage
    return len(GROWTH_STAGE_DAY_LIMITS)


def _estimate_height(days_since_planting):
    for limit, height in HEIGHT_BY_DAY_LIMIT:
        if days_since_planting < limit:
            return height
    return MATURE_HEIGHT_CM
