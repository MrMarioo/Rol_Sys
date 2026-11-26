"""Train the growth model on SYNTHETIC data.

No field measurements are used: every training sample is generated from hand-written
agronomic curves (logistic biomass growth, NDVI phenology, seasonal weather) plus noise.
The resulting model demonstrates the inference pipeline; it is not a validated agronomic model.

Usage: python train_growth_model.py [--output models/growth_model.pkl] [--seed 42]
"""

import argparse
import logging
from datetime import datetime
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import GradientBoostingRegressor, RandomForestRegressor
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler

from analytics.plant_growth_prediction import FEATURE_COLUMNS
from config import Config

logger = logging.getLogger("train_growth_model")

MODEL_VERSION = "1.0"
MIN_ACCEPTED_R2 = 0.7
TEST_SIZE = 0.2

CROPS = [
    {"name": "wheat", "cycle": 120, "max_biomass": 8000, "growth_rate": 0.08, "peak_day": 70, "max_height": 90},
    {"name": "corn", "cycle": 140, "max_biomass": 12000, "growth_rate": 0.06, "peak_day": 90, "max_height": 200},
    {"name": "barley", "cycle": 100, "max_biomass": 6000, "growth_rate": 0.09, "peak_day": 60, "max_height": 80},
    {"name": "rapeseed", "cycle": 110, "max_biomass": 7000, "growth_rate": 0.07, "peak_day": 75, "max_height": 120},
]
SEASONS = [
    {"temp_offset": 0, "moisture_factor": 1.0, "fertility": 1.0},
    {"temp_offset": 3, "moisture_factor": 0.8, "fertility": 1.1},
    {"temp_offset": -2, "moisture_factor": 1.2, "fertility": 0.9},
    {"temp_offset": 1, "moisture_factor": 0.9, "fertility": 1.05},
]
GROWTH_STAGE_PROGRESS_LIMITS = (0.12, 0.25, 0.50, 0.75, 0.90)


def growth_stage(day, cycle_days):
    progress = day / cycle_days
    for stage, limit in enumerate(GROWTH_STAGE_PROGRESS_LIMITS):
        if progress < limit:
            return stage
    return len(GROWTH_STAGE_PROGRESS_LIMITS)


def phenology_ndvi(day, cycle_days, crop):
    progress = day / cycle_days
    peak_ndvi = 0.85 if crop["name"] in ("wheat", "barley") else 0.90
    if progress < 0.12:
        return 0.20 + progress * 2
    if progress < 0.25:
        return 0.44 + (progress - 0.12) * 1.5
    if progress < 0.65:
        return 0.64 + (progress - 0.25) * (peak_ndvi - 0.64) / 0.4
    if progress < 0.85:
        return peak_ndvi - (progress - 0.65) * 0.4
    return 0.45 - (progress - 0.85) * 0.3


def plant_height(rng, day, cycle_days, crop):
    max_height = crop["max_height"]
    height = max_height / (1 + np.exp(-0.08 * (day - cycle_days * 0.6)))
    return max(2, height + rng.normal(0, max_height * 0.05))


def biomass(rng, day, crop, ndvi, moisture, temperature, fertilizer_total, fertility):
    base = crop["max_biomass"] / (1 + np.exp(-crop["growth_rate"] * (day - crop["peak_day"])))
    ndvi_factor = max(0.3, min(1.5, ndvi * 1.2))

    if 0.4 <= moisture <= 0.6:
        moisture_factor = 1.0
    elif moisture < 0.4:
        moisture_factor = max(0.5, moisture / 0.4)
    else:
        moisture_factor = max(0.7, 1.0 - (moisture - 0.6) * 0.5)

    if 18 <= temperature <= 25:
        temperature_factor = 1.0
    elif temperature < 18:
        temperature_factor = max(0.6, 0.8 + (temperature - 15) * 0.04)
    else:
        temperature_factor = max(0.5, 1.0 - (temperature - 25) * 0.02)

    fertilizer_factor = 1.0 + np.log(1 + fertilizer_total / 100) * 0.1
    value = base * ndvi_factor * moisture_factor * temperature_factor * fertilizer_factor * fertility
    return max(50, value * rng.normal(1.0, 0.08))


def generate_samples(rng):
    samples = []
    for crop, season in zip(CROPS, SEASONS, strict=True):
        cycle_days = crop["cycle"]
        for day in range(cycle_days):
            avg_ndvi = np.clip(phenology_ndvi(day, cycle_days, crop) + rng.normal(0, 0.05), 0.1, 0.95)

            rain_pulse = 0.2 if rng.random() < 0.25 else 0
            avg_moisture = np.clip(
                0.45 * season["moisture_factor"]
                + 0.15 * np.sin(day / 7 * 2 * np.pi)
                + rain_pulse
                + rng.normal(0, 0.08),
                0.05,
                0.95,
            )
            avg_temperature = 18 + season["temp_offset"] + 8 * np.sin(day / 365 * 2 * np.pi) + rng.normal(0, 3)
            rainfall = rng.exponential(3) if rng.random() < 0.3 else 0.0
            sunshine_hours = np.clip(8 + 4 * np.sin(day / 365 * 2 * np.pi) + rng.normal(0, 1.5), 3, 14)

            fertilizer_n = 120 if day in (10, 35) else (80 if day == 20 else 0)
            fertilizer_p = 60 if day in (15, 45) else 0
            fertilizer_k = 80 if day == 25 else 0

            target = biomass(
                rng,
                day,
                crop,
                avg_ndvi,
                avg_moisture,
                avg_temperature,
                fertilizer_n + fertilizer_p + fertilizer_k,
                season["fertility"],
            )
            if rng.random() < 0.05:
                target *= rng.uniform(0.6, 0.8)
                avg_ndvi *= rng.uniform(0.7, 0.9)

            samples.append(
                {
                    "avg_ndvi": avg_ndvi,
                    "min_ndvi": max(0.1, avg_ndvi - rng.uniform(0.1, 0.2)),
                    "max_ndvi": min(0.95, avg_ndvi + rng.uniform(0.05, 0.15)),
                    "avg_moisture": avg_moisture,
                    "min_moisture": max(0.05, avg_moisture - rng.uniform(0.1, 0.2)),
                    "max_moisture": min(0.95, avg_moisture + rng.uniform(0.05, 0.15)),
                    "avg_temperature": avg_temperature,
                    "soil_temperature": avg_temperature - 2 + rng.normal(0, 1),
                    "rainfall": rainfall,
                    "sunshine_hours": sunshine_hours,
                    "days_since_planting": day,
                    "growth_stage_encoded": growth_stage(day, cycle_days),
                    "fertilizer_nitrogen": fertilizer_n,
                    "fertilizer_phosphorus": fertilizer_p,
                    "fertilizer_potassium": fertilizer_k,
                    "field_size": 5.5 + rng.normal(0, 1.5),
                    "plant_height": plant_height(rng, day, cycle_days, crop),
                    "plant_density": 225 + rng.normal(0, 20),
                    "biomass_target": target,
                }
            )
    return pd.DataFrame(samples)


def train(seed):
    rng = np.random.default_rng(seed)
    df = generate_samples(rng)
    logger.info("Generated %d synthetic samples", len(df))

    x_train, x_test, y_train, y_test = train_test_split(
        df[FEATURE_COLUMNS], df["biomass_target"], test_size=TEST_SIZE, random_state=seed
    )
    scaler = StandardScaler()
    x_train_scaled = scaler.fit_transform(x_train)
    x_test_scaled = scaler.transform(x_test)

    candidates = {
        "RandomForest": RandomForestRegressor(n_estimators=150, max_depth=12, min_samples_split=5, random_state=seed),
        "GradientBoosting": GradientBoostingRegressor(
            n_estimators=150, max_depth=8, learning_rate=0.1, random_state=seed
        ),
    }

    best_name, best_model, best_r2 = None, None, -np.inf
    for name, model in candidates.items():
        model.fit(x_train_scaled, y_train)
        predictions = model.predict(x_test_scaled)
        r2 = r2_score(y_test, predictions)
        logger.info("%s: R2=%.4f MAE=%.2f", name, r2, mean_absolute_error(y_test, predictions))
        if r2 > best_r2:
            best_name, best_model, best_r2 = name, model, r2

    if best_r2 < MIN_ACCEPTED_R2:
        raise SystemExit(f"Best model R2 {best_r2:.4f} is below the {MIN_ACCEPTED_R2} acceptance threshold")

    logger.info("Selected %s (R2=%.4f)", best_name, best_r2)
    return {
        "model": best_model,
        "scaler": scaler,
        "feature_columns": FEATURE_COLUMNS,
        "training_date": datetime.now().isoformat(),
        "model_version": MODEL_VERSION,
        "model_type": type(best_model).__name__,
    }


def main():
    parser = argparse.ArgumentParser(description="Train the growth model on synthetic data.")
    parser.add_argument("--output", type=Path, default=Config.MODEL_PATH)
    parser.add_argument("--seed", type=int, default=42)
    args = parser.parse_args()

    logging.basicConfig(level=logging.INFO, format="%(levelname)s %(message)s")
    model_data = train(args.seed)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(model_data, args.output)
    logger.info("Model written to %s", args.output)


if __name__ == "__main__":
    main()
