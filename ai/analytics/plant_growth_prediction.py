import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, r2_score, mean_absolute_error
import joblib
import json
import os
from datetime import datetime, timedelta
import logging

logger = logging.getLogger(__name__)


class PlantGrowthPredictor:
    def __init__(self, model_path='/app/models/growth_model.pkl'):
        self.model = None
        self.scaler = StandardScaler()
        self.feature_columns = [
            'avg_ndvi', 'min_ndvi', 'max_ndvi',
            'avg_moisture', 'min_moisture', 'max_moisture',
            'avg_temperature', 'soil_temperature', 'rainfall', 'sunshine_hours',
            'days_since_planting', 'growth_stage_encoded',
            'fertilizer_nitrogen', 'fertilizer_phosphorus', 'fertilizer_potassium',
            'field_size', 'plant_height', 'plant_density'
        ]
        self.model_path = model_path
        self.model_version = "1.0"
        self.training_date = None

        # Automatyczna inicjalizacja
        self.auto_initialize()

    def auto_initialize(self):
        """Automatyczna inicjalizacja - wczytaj lub stwórz model"""
        logger.info("🚀 Initializing PlantGrowthPredictor...")

        # Stwórz katalog na modele jeśli nie istnieje
        os.makedirs(os.path.dirname(self.model_path), exist_ok=True)

        if os.path.exists(self.model_path):
            try:
                self.load_model()
                logger.info("✅ Existing growth prediction model loaded successfully")
                return True
            except Exception as e:
                logger.warning(f"⚠️  Error loading existing model: {e}")
                logger.info("🔄 Will create new model...")

        # Jeśli model nie istnieje lub nie można go wczytać, stwórz nowy
        logger.info("🔧 Creating new growth prediction model...")

        if self.create_and_train_model():
            logger.info("✅ New growth prediction model created and trained successfully")
            return True
        else:
            logger.error("❌ Failed to create growth prediction model")
            self.create_fallback_model()
            return False

    def create_and_train_model(self):
        """Stwórz i wytrenuj model automatycznie"""
        try:
            logger.info("📊 Generating training data...")
            training_data = self.generate_comprehensive_training_data()

            if len(training_data) < 200:
                logger.error(f"❌ Not enough training data: {len(training_data)} samples")
                return False

            logger.info(f"📈 Training model with {len(training_data)} samples...")

            # Przygotuj DataFrame
            df = pd.DataFrame(training_data)
            X = df[self.feature_columns]
            y = df['biomass_target']

            # Wypełnij brakujące wartości
            X = X.fillna(X.mean())

            # Podział na zbiory treningowy i testowy
            X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

            # Skalowanie
            X_train_scaled = self.scaler.fit_transform(X_train)
            X_test_scaled = self.scaler.transform(X_test)

            # Trenuj różne modele i wybierz najlepszy
            models = {
                'RandomForest': RandomForestRegressor(
                    n_estimators=150,
                    max_depth=12,
                    min_samples_split=5,
                    random_state=42,
                    n_jobs=-1
                ),
                'GradientBoosting': GradientBoostingRegressor(
                    n_estimators=150,
                    max_depth=8,
                    learning_rate=0.1,
                    random_state=42
                )
            }

            best_model = None
            best_score = -float('inf')
            best_name = ""

            for name, model in models.items():
                # Trenuj model
                model.fit(X_train_scaled, y_train)

                # Ewaluacja
                y_pred = model.predict(X_test_scaled)
                r2 = r2_score(y_test, y_pred)
                mae = mean_absolute_error(y_test, y_pred)

                logger.info(f"📊 {name} - R²: {r2:.4f}, MAE: {mae:.2f}")

                if r2 > best_score:
                    best_score = r2
                    best_model = model
                    best_name = name

            if best_score > 0.7:  # Próg akceptacji
                self.model = best_model
                self.training_date = datetime.now().isoformat()

                logger.info(f"🏆 Selected {best_name} as best model (R²: {best_score:.4f})")

                # Zapisz model
                self.save_model()

                # Wyświetl ważność cech
                if hasattr(self.model, 'feature_importances_'):
                    self.log_feature_importance()

                return True
            else:
                logger.error(f"❌ Model quality too low (R²: {best_score:.4f} < 0.7)")
                return False

        except Exception as e:
            logger.error(f"❌ Error during model training: {e}")
            return False

    def create_fallback_model(self):
        """Stwórz prosty model fallback jeśli główne trenowanie nie powiedzie się"""
        logger.info("🆘 Creating fallback model...")

        try:
            # Minimalne dane treningowe
            np.random.seed(42)
            n_samples = 300

            # Podstawowe syntetyczne dane
            X_data = {}
            for feature in self.feature_columns:
                if feature == 'days_since_planting':
                    X_data[feature] = np.random.randint(1, 120, n_samples)
                elif feature == 'growth_stage_encoded':
                    X_data[feature] = np.random.randint(0, 6, n_samples)
                elif 'ndvi' in feature:
                    X_data[feature] = np.random.uniform(0.2, 0.9, n_samples)
                elif 'moisture' in feature:
                    X_data[feature] = np.random.uniform(0.1, 0.8, n_samples)
                elif 'temperature' in feature:
                    X_data[feature] = np.random.uniform(5, 35, n_samples)
                elif 'fertilizer' in feature:
                    X_data[feature] = np.random.choice([0, 50, 100, 150], n_samples)
                else:
                    X_data[feature] = np.random.uniform(0, 100, n_samples)

            X = pd.DataFrame(X_data)

            # Prosta formuła dla biomasy
            y = (8000 / (1 + np.exp(-0.08 * (X['days_since_planting'] - 70))) *
                 (1 + X['avg_ndvi']) * 0.5 +
                 np.random.normal(0, 200, n_samples))

            # Trenuj prosty model
            self.scaler.fit(X)
            X_scaled = self.scaler.transform(X)

            self.model = RandomForestRegressor(n_estimators=50, random_state=42)
            self.model.fit(X_scaled, y)

            self.training_date = datetime.now().isoformat()
            self.save_model()

            logger.info("✅ Fallback model created successfully")
            return True

        except Exception as e:
            logger.error(f"❌ Failed to create fallback model: {e}")
            return False

    def generate_comprehensive_training_data(self):
        """Generuj kompleksowe, realistyczne dane treningowe"""
        logger.info("🌱 Generating comprehensive training data...")
        training_data = []

        # Symuluj 4 sezony różnych upraw
        crops_config = [
            {'name': 'wheat', 'cycle': 120, 'max_biomass': 8000, 'growth_rate': 0.08, 'peak_day': 70},
            {'name': 'corn', 'cycle': 140, 'max_biomass': 12000, 'growth_rate': 0.06, 'peak_day': 90},
            {'name': 'barley', 'cycle': 100, 'max_biomass': 6000, 'growth_rate': 0.09, 'peak_day': 60},
            {'name': 'rapeseed', 'cycle': 110, 'max_biomass': 7000, 'growth_rate': 0.07, 'peak_day': 75}
        ]

        for season in range(len(crops_config)):
            crop_config = crops_config[season]
            cycle_days = crop_config['cycle']

            # Różne warunki dla każdego sezonu
            season_factor = {
                'temp_offset': [0, 3, -2, 1][season],  # Różne temperatury
                'moisture_factor': [1.0, 0.8, 1.2, 0.9][season],  # Różna wilgotność
                'fertility': [1.0, 1.1, 0.9, 1.05][season]  # Różna żyzność
            }

            for day in range(cycle_days):
                # Podstawowe parametry czasowe
                days_since_planting = day
                growth_stage = self.calculate_growth_stage(day, cycle_days)

                # NDVI z realistyczną krzywą wzrostu
                base_ndvi = self.calculate_realistic_ndvi(day, cycle_days, crop_config)
                ndvi_noise = np.random.normal(0, 0.05)
                avg_ndvi = np.clip(base_ndvi + ndvi_noise, 0.1, 0.95)

                # Wilgotność gleby z sezonowymi wzorcami
                base_moisture = 0.45 * season_factor['moisture_factor']
                moisture_cycle = 0.15 * np.sin((day / 7) * 2 * np.pi)  # Tygodniowy cykl
                rainfall_effect = 0.2 if np.random.random() < 0.25 else 0
                avg_moisture = np.clip(base_moisture + moisture_cycle + rainfall_effect + np.random.normal(0, 0.08),
                                       0.05, 0.95)

                # Temperatura z sezonowymi zmianami
                base_temp = 18 + season_factor['temp_offset'] + 8 * np.sin((day / 365) * 2 * np.pi)
                daily_variation = np.random.normal(0, 3)
                avg_temperature = base_temp + daily_variation
                soil_temperature = avg_temperature - 2 + np.random.normal(0, 1)

                # Opady i nasłonecznienie
                rainfall = max(0, np.random.exponential(3) if np.random.random() < 0.3 else 0)
                sunshine_hours = np.clip(8 + 4 * np.sin((day / 365) * 2 * np.pi) + np.random.normal(0, 1.5), 3, 14)

                # Nawożenie w kluczowych momentach
                fertilizer_n = 120 if day in [10, 35] else (80 if day == 20 else 0)
                fertilizer_p = 60 if day in [15, 45] else 0
                fertilizer_k = 80 if day == 25 else 0

                # Parametry fizyczne pola
                field_size = 5.5 + np.random.normal(0, 1.5)
                plant_height = self.calculate_realistic_height(day, cycle_days, crop_config)
                plant_density = 225 + np.random.normal(0, 20)

                # Target - biomasa z realistycznym modelem wzrostu
                biomass_target = self.calculate_realistic_biomass(
                    day, crop_config, avg_ndvi, avg_moisture,
                    avg_temperature, fertilizer_n + fertilizer_p + fertilizer_k,
                    season_factor['fertility']
                )

                # Dodaj szum i anomalie
                if np.random.random() < 0.05:  # 5% szans na anomalię
                    biomass_target *= np.random.uniform(0.6, 0.8)  # Redukcja biomasy
                    avg_ndvi *= np.random.uniform(0.7, 0.9)  # Odpowiadające obniżenie NDVI

                # Stwórz próbkę
                sample = {
                    'avg_ndvi': float(avg_ndvi),
                    'min_ndvi': float(max(0.1, avg_ndvi - np.random.uniform(0.1, 0.2))),
                    'max_ndvi': float(min(0.95, avg_ndvi + np.random.uniform(0.05, 0.15))),
                    'avg_moisture': float(avg_moisture),
                    'min_moisture': float(max(0.05, avg_moisture - np.random.uniform(0.1, 0.2))),
                    'max_moisture': float(min(0.95, avg_moisture + np.random.uniform(0.05, 0.15))),
                    'avg_temperature': float(avg_temperature),
                    'soil_temperature': float(soil_temperature),
                    'rainfall': float(rainfall),
                    'sunshine_hours': float(sunshine_hours),
                    'days_since_planting': int(days_since_planting),
                    'growth_stage_encoded': int(growth_stage),
                    'fertilizer_nitrogen': float(fertilizer_n),
                    'fertilizer_phosphorus': float(fertilizer_p),
                    'fertilizer_potassium': float(fertilizer_k),
                    'field_size': float(field_size),
                    'plant_height': float(plant_height),
                    'plant_density': float(plant_density),
                    'biomass_target': float(biomass_target)
                }

                training_data.append(sample)

        logger.info(f"✅ Generated {len(training_data)} comprehensive training samples")
        return training_data

    def calculate_growth_stage(self, day, cycle_days):
        """Oblicz fazę wzrostu na podstawie dnia i długości cyklu"""
        progress = day / cycle_days
        if progress < 0.12:
            return 0  # Kiełkowanie (0-12%)
        elif progress < 0.25:
            return 1  # Wczesny wzrost (12-25%)
        elif progress < 0.50:
            return 2  # Wzrost wegetatywny (25-50%)
        elif progress < 0.75:
            return 3  # Kwitnienie (50-75%)
        elif progress < 0.90:
            return 4  # Dojrzewanie (75-90%)
        else:
            return 5  # Dojrzałość (90-100%)

    def calculate_realistic_ndvi(self, day, cycle_days, crop_config):
        """Oblicz realistyczne NDVI na podstawie krzywej wzrostu"""
        progress = day / cycle_days

        # Krzywa NDVI specyficzna dla uprowy
        if progress < 0.12:  # Kiełkowanie
            return 0.20 + progress * 2  # 0.2 -> 0.44
        elif progress < 0.25:  # Wczesny wzrost
            return 0.44 + (progress - 0.12) * 1.5  # 0.44 -> 0.64
        elif progress < 0.65:  # Szczyt wegetatywny
            peak_ndvi = 0.85 if crop_config['name'] in ['wheat', 'barley'] else 0.90
            return 0.64 + (progress - 0.25) * (peak_ndvi - 0.64) / 0.4
        elif progress < 0.85:  # Dojrzewanie
            peak_ndvi = 0.85 if crop_config['name'] in ['wheat', 'barley'] else 0.90
            return peak_ndvi - (progress - 0.65) * 0.4  # Spadek
        else:  # Dojrzałość
            return 0.45 - (progress - 0.85) * 0.3  # Dalszy spadek

    def calculate_realistic_height(self, day, cycle_days, crop_config):
        """Oblicz realistyczną wysokość rośliny"""
        max_heights = {
            'wheat': 90, 'corn': 200, 'barley': 80, 'rapeseed': 120
        }
        max_height = max_heights.get(crop_config['name'], 90)

        # Logistyczna krzywa wzrostu wysokości
        growth_rate = 0.08
        inflection_point = cycle_days * 0.6  # 60% cyklu

        height = max_height / (1 + np.exp(-growth_rate * (day - inflection_point)))
        return max(2, height + np.random.normal(0, max_height * 0.05))

    def calculate_realistic_biomass(self, day, crop_config, ndvi, moisture, temperature, fertilizer_total,
                                    fertility_factor):
        """Oblicz realistyczną biomasę z uwzględnieniem wszystkich czynników"""
        # Bazowa krzywa wzrostu
        max_biomass = crop_config['max_biomass']
        growth_rate = crop_config['growth_rate']
        peak_day = crop_config['peak_day']

        base_biomass = max_biomass / (1 + np.exp(-growth_rate * (day - peak_day)))

        # Modyfikatory środowiskowe
        ndvi_factor = max(0.3, min(1.5, ndvi * 1.2))  # NDVI wpływa na biomasę

        # Wilgotność - optymalna w zakresie 0.4-0.6
        if 0.4 <= moisture <= 0.6:
            moisture_factor = 1.0
        elif moisture < 0.4:
            moisture_factor = max(0.5, moisture / 0.4)
        else:
            moisture_factor = max(0.7, 1.0 - (moisture - 0.6) * 0.5)

        # Temperatura - optymalna w zakresie 18-25°C
        if 18 <= temperature <= 25:
            temp_factor = 1.0
        elif temperature < 18:
            temp_factor = max(0.6, 0.8 + (temperature - 15) * 0.04)
        else:
            temp_factor = max(0.5, 1.0 - (temperature - 25) * 0.02)

        # Nawożenie - wpływ logarytmiczny
        fertilizer_factor = 1.0 + np.log(1 + fertilizer_total / 100) * 0.1

        # Czynnik żyzności gleby
        soil_factor = fertility_factor

        # Oblicz finalną biomasę
        final_biomass = (base_biomass * ndvi_factor * moisture_factor *
                         temp_factor * fertilizer_factor * soil_factor)

        # Dodaj naturalną zmienność
        variation = np.random.normal(1.0, 0.08)
        final_biomass *= variation

        return max(50, final_biomass)  # Minimalna biomasa 50 kg/ha

    def log_feature_importance(self):
        """Wyloguj ważność cech modelu"""
        if hasattr(self.model, 'feature_importances_'):
            importance_df = pd.DataFrame({
                'feature': self.feature_columns,
                'importance': self.model.feature_importances_
            }).sort_values('importance', ascending=False)

            logger.info("🎯 Top 10 Most Important Features:")
            for _, row in importance_df.head(10).iterrows():
                logger.info(f"   {row['feature']}: {row['importance']:.4f}")

    def prepare_features(self, field_data, field_info):
        """Przygotowanie cech do predykcji z danych pola"""
        features = {}

        # NDVI features z ostatnich 7 dni
        ndvi_data = [item for item in field_data if item.get('data_type') == 'ndvi']
        if ndvi_data:
            all_ndvi = []
            for item in ndvi_data[-7:]:  # Ostatnie 7 dni
                data = item.get('data', {})
                if isinstance(data, str):
                    try:
                        data = json.loads(data)
                    except:
                        continue
                ndvi_values = data.get('ndvi_values', [])
                all_ndvi.extend(ndvi_values)

            if all_ndvi:
                features['avg_ndvi'] = np.mean(all_ndvi)
                features['min_ndvi'] = np.min(all_ndvi)
                features['max_ndvi'] = np.max(all_ndvi)
            else:
                features.update({'avg_ndvi': 0.5, 'min_ndvi': 0.3, 'max_ndvi': 0.7})
        else:
            features.update({'avg_ndvi': 0.5, 'min_ndvi': 0.3, 'max_ndvi': 0.7})

        # Soil moisture features z ostatnich 7 dni
        moisture_data = [item for item in field_data if item.get('data_type') == 'soil_moisture']
        if moisture_data:
            all_moisture = []
            for item in moisture_data[-7:]:
                data = item.get('data', {})
                if isinstance(data, str):
                    try:
                        data = json.loads(data)
                    except:
                        continue
                moisture_values = data.get('moisture_values', [])
                all_moisture.extend(moisture_values)

            if all_moisture:
                features['avg_moisture'] = np.mean(all_moisture)
                features['min_moisture'] = np.min(all_moisture)
                features['max_moisture'] = np.max(all_moisture)
            else:
                features.update({'avg_moisture': 0.45, 'min_moisture': 0.25, 'max_moisture': 0.65})
        else:
            features.update({'avg_moisture': 0.45, 'min_moisture': 0.25, 'max_moisture': 0.65})

        # Weather features z ostatnich 3 dni
        weather_data = [item for item in field_data if item.get('data_type') == 'weather']
        if weather_data:
            recent_weather = weather_data[-3:]
            temps = []
            rainfall_total = 0

            for item in recent_weather:
                data = item.get('data', {})
                if isinstance(data, str):
                    try:
                        data = json.loads(data)
                    except:
                        continue
                temps.append(data.get('temperature_avg', 20))
                rainfall_total += data.get('rainfall', 0)

            features['avg_temperature'] = np.mean(temps) if temps else 20
            features['rainfall'] = rainfall_total
        else:
            features['avg_temperature'] = 20
            features['rainfall'] = 0

        # Soil temperature
        soil_temp_data = [item for item in field_data if item.get('data_type') == 'soil_temperature']
        if soil_temp_data:
            recent_temp = soil_temp_data[-1]
            data = recent_temp.get('data', {})
            if isinstance(data, str):
                try:
                    data = json.loads(data)
                except:
                    data = {}
            features['soil_temperature'] = data.get('avg_temperature', 16)
        else:
            features['soil_temperature'] = 16

        # Sunlight z ostatnich 3 dni
        sunlight_data = [item for item in field_data if item.get('data_type') == 'sunlight']
        if sunlight_data:
            recent_sun = sunlight_data[-3:]
            sun_hours = []
            for item in recent_sun:
                data = item.get('data', {})
                if isinstance(data, str):
                    try:
                        data = json.loads(data)
                    except:
                        continue
                sun_hours.append(data.get('sunshine_hours', 8))
            features['sunshine_hours'] = np.mean(sun_hours) if sun_hours else 8
        else:
            features['sunshine_hours'] = 8

        # Time and growth stage features
        features['days_since_planting'] = self._calculate_days_since_planting(field_info)
        features['growth_stage_encoded'] = self._determine_growth_stage(features['days_since_planting'])

        # Fertilizer features z ostatnich 30 dni
        fertilizer_data = [item for item in field_data if item.get('data_type') == 'fertilizer_application']
        features['fertilizer_nitrogen'] = 0
        features['fertilizer_phosphorus'] = 0
        features['fertilizer_potassium'] = 0

        for item in fertilizer_data:
            data = item.get('data', {})
            if isinstance(data, str):
                try:
                    data = json.loads(data)
                except:
                    continue

            fert_type = data.get('fertilizer_type', '').lower()
            amount = data.get('amount_per_hectare', 0)

            if 'nitrogen' in fert_type or 'n' in fert_type:
                features['fertilizer_nitrogen'] += amount
            elif 'phosphorus' in fert_type or 'p' in fert_type:
                features['fertilizer_phosphorus'] += amount
            elif 'potassium' in fert_type or 'k' in fert_type:
                features['fertilizer_potassium'] += amount

        # Field and plant features
        features['field_size'] = field_info.get('size', 5.5)

        # Plant features z ostatnich pomiarów biomasy
        biomass_data = [item for item in field_data if item.get('data_type') == 'biomass_measurement']
        if biomass_data:
            latest_biomass = biomass_data[-1]
            data = latest_biomass.get('data', {})
            if isinstance(data, str):
                try:
                    data = json.loads(data)
                except:
                    data = {}

            features['plant_height'] = data.get('plant_height_cm', 50)
            features['plant_density'] = data.get('plant_density_per_m2', 225)
        else:
            # Oszacuj na podstawie dni od sadzenia
            features['plant_height'] = self._estimate_height(features['days_since_planting'])
            features['plant_density'] = 225

        return features

    def predict_growth(self, field_data, field_info, days_ahead=7):
        """Główna funkcja predykcji wzrostu roślin"""
        if not self.model:
            return {'error': 'Prediction model not available'}

        try:
            current_features = self.prepare_features(field_data, field_info)
            current_biomass = self._estimate_current_biomass(current_features)

            predictions = []

            for day in range(1, days_ahead + 1):
                # Przygotuj cechy dla przyszłego dnia
                future_features = current_features.copy()
                future_features['days_since_planting'] += day
                future_features['growth_stage_encoded'] = self._determine_growth_stage(
                    future_features['days_since_planting']
                )

                # Oszacuj zmiany pogodowe (uproszczone prognozy)
                future_features['avg_temperature'] += np.random.normal(0, 2)
                future_features['rainfall'] = max(0, np.random.exponential(2) if np.random.random() < 0.2 else 0)
                future_features['sunshine_hours'] = max(4, min(12,
                                                               future_features['sunshine_hours'] + np.random.normal(0,
                                                                                                                    1)))

                # Oszacuj zmiany NDVI i wilgotności
                ndvi_change = 0.002 if future_features['growth_stage_encoded'] <= 3 else -0.005
                future_features['avg_ndvi'] = np.clip(
                    future_features['avg_ndvi'] + ndvi_change, 0.1, 0.95)

                moisture_change = 0.02 if future_features['rainfall'] > 5 else -0.01
                future_features['avg_moisture'] = np.clip(
                    future_features['avg_moisture'] + moisture_change, 0.1, 0.9)

                # Przewiduj biomasę
                try:
                    feature_array = np.array([future_features[col] for col in self.feature_columns]).reshape(1, -1)
                    feature_array_scaled = self.scaler.transform(feature_array)
                    predicted_biomass = self.model.predict(feature_array_scaled)[0]
                except Exception as e:
                    logger.warning(f"Prediction error for day {day}: {e}")
                    # Fallback prediction
                    predicted_biomass = current_biomass * (1 + 0.015 * day)

                # Oblicz tempo wzrostu
                previous_biomass = predictions[-1]['predicted_biomass'] if predictions else current_biomass
                growth_rate = predicted_biomass - previous_biomass

                predictions.append({
                    'day': day,
                    'date': (datetime.now() + timedelta(days=day)).strftime('%Y-%m-%d'),
                    'predicted_biomass': float(max(current_biomass, predicted_biomass)),
                    'growth_rate': float(growth_rate),
                    'confidence': self._calculate_confidence(current_features, day),
                    'growth_stage': self._get_growth_stage_name(future_features['growth_stage_encoded']),
                    'estimated_ndvi': float(future_features['avg_ndvi']),
                    'estimated_moisture': float(future_features['avg_moisture'])
                })

            # Przygotuj kompletną odpowiedź
            result = {
                'current_status': self._assess_current_status(current_features),
                'current_biomass': float(current_biomass),
                'predictions': predictions,
                'recommendations': self._generate_growth_recommendations(predictions, current_features),
                'summary': self._generate_prediction_summary(predictions, current_features),
                'model_info': {
                    'model_type': type(self.model).__name__,
                    'features_used': len(self.feature_columns),
                    'training_date': self.training_date,
                    'version': self.model_version
                }
            }

            return result

        except Exception as e:
            logger.error(f"Prediction failed: {e}")
            return {'error': f'Prediction failed: {str(e)}'}

    def _estimate_current_biomass(self, features):
        """Oszacuj obecną biomasę na podstawie cech"""
        try:
            feature_array = np.array([features[col] for col in self.feature_columns]).reshape(1, -1)
            feature_array_scaled = self.scaler.transform(feature_array)
            predicted_biomass = self.model.predict(feature_array_scaled)[0]
            return max(100, predicted_biomass)
        except Exception as e:
            logger.warning(f"Current biomass estimation failed: {e}")
            # Fallback calculation
            base_biomass = 8000 / (1 + np.exp(-0.08 * (features['days_since_planting'] - 70)))
            ndvi_factor = max(0.5, features['avg_ndvi'])
            return base_biomass * ndvi_factor

    def _calculate_days_since_planting(self, field_info):
        """Oblicz dni od sadzenia"""
        planting_date = field_info.get('planting_date')
        if planting_date:
            if isinstance(planting_date, str):
                try:
                    planting_date = datetime.strptime(planting_date, '%Y-%m-%d')
                    days = (datetime.now() - planting_date).days
                    return max(1, days)  # Minimum 1 dzień
                except:
                    pass
        return 50  # Default dla środka sezonu

    def _determine_growth_stage(self, days_since_planting):
        """Określ fazę wzrostu (0-5)"""
        if days_since_planting < 14:
            return 0  # Kiełkowanie
        elif days_since_planting < 30:
            return 1  # Wzrost wegetatywny wczesny
        elif days_since_planting < 60:
            return 2  # Wzrost wegetatywny
        elif days_since_planting < 90:
            return 3  # Kwitnienie
        elif days_since_planting < 110:
            return 4  # Formowanie plonów
        else:
            return 5  # Dojrzałość

    def _get_growth_stage_name(self, stage_encoded):
        """Nazwa fazy wzrostu"""
        stages = {
            0: 'Germination',
            1: 'Early Vegetative',
            2: 'Vegetative Growth',
            3: 'Flowering',
            4: 'Grain Filling',
            5: 'Maturity'
        }
        return stages.get(stage_encoded, 'Unknown')

    def _estimate_height(self, days_since_planting):
        """Oszacuj wysokość na podstawie dni"""
        if days_since_planting < 14: return 5
        if days_since_planting < 30: return 15
        if days_since_planting < 60: return 45
        if days_since_planting < 90: return 80
        return 90

    def _assess_current_status(self, features):
        """Kompleksowa ocena obecnego stanu"""
        status = "normal"
        issues = []
        warnings = []

        # Sprawdź NDVI (health indicator)
        avg_ndvi = features['avg_ndvi']
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

        # Sprawdź wilgotność gleby
        avg_moisture = features['avg_moisture']
        if avg_moisture < 0.2:
            issues.append(f"Critically low soil moisture ({avg_moisture:.2f})")
            if status not in ["critical", "poor"]:
                status = "poor"
        elif avg_moisture < 0.3:
            warnings.append(f"Low soil moisture ({avg_moisture:.2f})")
        elif avg_moisture > 0.8:
            warnings.append(f"Very high soil moisture ({avg_moisture:.2f}) - risk of waterlogging")

        # Sprawdź temperaturę
        avg_temp = features['avg_temperature']
        if avg_temp > 32:
            warnings.append(f"High temperature stress risk ({avg_temp:.1f}°C)")
        elif avg_temp < 8:
            warnings.append(f"Low temperature may slow growth ({avg_temp:.1f}°C)")

        # Sprawdź fazę wzrostu
        growth_stage = self._get_growth_stage_name(features['growth_stage_encoded'])
        days_planted = features['days_since_planting']

        # Oblicz ogólny wskaźnik zdrowia (0-100)
        health_score = min(100, max(0, (
            avg_ndvi * 40 +  # NDVI ma największy wpływ
            (1.0 if 0.3 <= avg_moisture <= 0.7 else max(0.3, 1.0 - abs(avg_moisture - 0.5))) * 30 +
            (1.0 if 15 <= avg_temp <= 25 else max(0.3, 1.0 - abs(avg_temp - 20) / 10)) * 20 +
            0.1 * 10  # Bonus za brak krytycznych problemów
        )))

        return {
            'status': status,
            'growth_stage': growth_stage,
            'days_since_planting': days_planted,
            'issues': issues,
            'warnings': warnings,
            'health_score': round(health_score, 1),
            'key_metrics': {
                'ndvi': round(avg_ndvi, 3),
                'soil_moisture': round(avg_moisture, 3),
                'temperature': round(avg_temp, 1)
            }
        }

    def _generate_growth_recommendations(self, predictions, current_features):
        """Generowanie szczegółowych zaleceń"""
        recommendations = []

        # Analiza trendu wzrostu
        if len(predictions) >= 3:
            growth_rates = [p['growth_rate'] for p in predictions[:3]]
            avg_growth_rate = np.mean(growth_rates)

            if avg_growth_rate < 30:  # Bardzo wolny wzrost
                recommendations.append({
                    'type': 'urgent',
                    'priority': 'high',
                    'category': 'growth',
                    'action': 'Investigate slow growth',
                    'description': f'Very slow growth rate detected ({avg_growth_rate:.1f} kg/ha/day). Normal rate: 60-120 kg/ha/day.',
                    'urgency_days': 3,
                    'estimated_impact': 'High'
                })
            elif avg_growth_rate < 50:
                recommendations.append({
                    'type': 'warning',
                    'priority': 'medium',
                    'category': 'nutrition',
                    'action': 'Consider nitrogen fertilization',
                    'description': f'Below-optimal growth rate ({avg_growth_rate:.1f} kg/ha/day). Consider nutrient analysis.',
                    'urgency_days': 7,
                    'estimated_impact': 'Medium'
                })

        # Zalecenia na podstawie wilgotności
        moisture = current_features['avg_moisture']
        if moisture < 0.25:
            recommendations.append({
                'type': 'urgent',
                'priority': 'high',
                'category': 'irrigation',
                'action': 'Immediate irrigation required',
                'description': f'Critically low soil moisture ({moisture:.2f}). Target range: 0.4-0.6.',
                'urgency_days': 1,
                'estimated_impact': 'Critical'
            })
        elif moisture < 0.35:
            recommendations.append({
                'type': 'high',
                'priority': 'high',
                'category': 'irrigation',
                'action': 'Increase irrigation',
                'description': f'Low soil moisture ({moisture:.2f}). Increase watering frequency.',
                'urgency_days': 2,
                'estimated_impact': 'High'
            })
        elif moisture > 0.75:
            recommendations.append({
                'type': 'caution',
                'priority': 'medium',
                'category': 'irrigation',
                'action': 'Reduce irrigation',
                'description': f'High soil moisture ({moisture:.2f}). Risk of root diseases and nutrient leaching.',
                'urgency_days': 3,
                'estimated_impact': 'Medium'
            })

        # Zalecenia na podstawie NDVI
        ndvi = current_features['avg_ndvi']
        if ndvi < 0.3:
            recommendations.append({
                'type': 'urgent',
                'priority': 'high',
                'category': 'health',
                'action': 'Comprehensive crop health assessment',
                'description': f'Very low NDVI ({ndvi:.2f}) indicates serious health issues. Check for pests, diseases, nutrient deficiency.',
                'urgency_days': 1,
                'estimated_impact': 'Critical'
            })
        elif ndvi < 0.5:
            recommendations.append({
                'type': 'warning',
                'priority': 'medium',
                'category': 'health',
                'action': 'Monitor crop condition closely',
                'description': f'Below-average NDVI ({ndvi:.2f}). Consider foliar feeding or pest inspection.',
                'urgency_days': 5,
                'estimated_impact': 'Medium'
            })

        # Zalecenia sezonowe na podstawie fazy wzrostu
        days_planted = current_features['days_since_planting']
        growth_stage = current_features['growth_stage_encoded']

        # Nawożenie w odpowiednich fazach
        if growth_stage == 1 and current_features['fertilizer_nitrogen'] < 60:  # Wczesny wzrost
            recommendations.append({
                'type': 'scheduled',
                'priority': 'medium',
                'category': 'nutrition',
                'action': 'Apply nitrogen fertilizer',
                'description': 'Optimal timing for nitrogen application during early vegetative growth (80-120 kg/ha recommended).',
                'urgency_days': 5,
                'estimated_impact': 'High'
            })

        if growth_stage == 2 and current_features['fertilizer_phosphorus'] < 40:  # Wzrost wegetatywny
            recommendations.append({
                'type': 'scheduled',
                'priority': 'medium',
                'category': 'nutrition',
                'action': 'Consider phosphorus application',
                'description': 'Phosphorus supports root development and energy transfer during vegetative growth.',
                'urgency_days': 10,
                'estimated_impact': 'Medium'
            })

        if growth_stage == 3 and current_features['fertilizer_potassium'] < 50:  # Kwitnienie
            recommendations.append({
                'type': 'scheduled',
                'priority': 'medium',
                'category': 'nutrition',
                'action': 'Apply potassium fertilizer',
                'description': 'Potassium is crucial during flowering for fruit/grain development.',
                'urgency_days': 7,
                'estimated_impact': 'High'
            })

        # Zalecenia pogodowe
        temp = current_features['avg_temperature']
        if temp > 30:
            recommendations.append({
                'type': 'caution',
                'priority': 'medium',
                'category': 'weather',
                'action': 'Heat stress mitigation',
                'description': f'High temperatures ({temp:.1f}°C) may cause stress. Ensure adequate water supply.',
                'urgency_days': 2,
                'estimated_impact': 'Medium'
            })

        # Predykcyjne zalecenia na podstawie trendów
        if len(predictions) >= 5:
            future_ndvi_trend = np.mean([p['estimated_ndvi'] for p in predictions[-3:]])
            if future_ndvi_trend < ndvi - 0.1:  # Spadający trend NDVI
                recommendations.append({
                    'type': 'predictive',
                    'priority': 'medium',
                    'category': 'health',
                    'action': 'Prepare for declining crop health',
                    'description': 'Model predicts declining NDVI. Consider preventive measures.',
                    'urgency_days': 5,
                    'estimated_impact': 'Medium'
                })

        # Pozytywne zalecenia jeśli wszystko w porządku
        if not recommendations or all(r['priority'] == 'low' for r in recommendations):
            recommendations.append({
                'type': 'positive',
                'priority': 'low',
                'category': 'general',
                'action': 'Continue current management',
                'description': 'Crop conditions are good. Maintain current practices and monitor regularly.',
                'urgency_days': 14,
                'estimated_impact': 'Low'
            })

        # Sortuj według priorytetu
        priority_order = {'high': 3, 'medium': 2, 'low': 1}
        recommendations.sort(key=lambda x: (
            priority_order.get(x['priority'], 0),
            -x['urgency_days']  # Więcej pilne najpierw
        ), reverse=True)

        return recommendations[:8]  # Maksymalnie 8 najważniejszych zaleceń

    def _generate_prediction_summary(self, predictions, current_features):
        """Generuj podsumowanie predykcji"""
        if not predictions:
            return {}

        total_growth = predictions[-1]['predicted_biomass'] - predictions[0]['predicted_biomass']
        avg_daily_growth = total_growth / len(predictions)
        max_growth_day = max(predictions, key=lambda x: x['growth_rate'])

        return {
            'total_predicted_growth': round(total_growth, 1),
            'average_daily_growth': round(avg_daily_growth, 1),
            'peak_growth_day': max_growth_day['day'],
            'peak_growth_rate': round(max_growth_day['growth_rate'], 1),
            'final_biomass': round(predictions[-1]['predicted_biomass'], 1),
            'confidence_range': {
                'min': round(min(p['confidence'] for p in predictions), 2),
                'max': round(max(p['confidence'] for p in predictions), 2),
                'avg': round(np.mean([p['confidence'] for p in predictions]), 2)
            }
        }

    def _calculate_confidence(self, features, days_ahead):
        """Oblicz poziom pewności predykcji"""
        base_confidence = 0.95

        # Zmniejsz pewność w zależności od odległości czasowej
        time_penalty = 0.02 * days_ahead

        # Zmniejsz pewność dla skrajnych wartości
        ndvi_penalty = 0.1 if features['avg_ndvi'] < 0.2 or features['avg_ndvi'] > 0.9 else 0
        moisture_penalty = 0.1 if features['avg_moisture'] < 0.1 or features['avg_moisture'] > 0.8 else 0

        # Zmniejsz pewność dla bardzo wczesnych lub późnych faz
        stage_penalty = 0.05 if features['days_since_planting'] < 10 or features['days_since_planting'] > 120 else 0

        final_confidence = base_confidence - time_penalty - ndvi_penalty - moisture_penalty - stage_penalty

        return max(0.4, min(0.95, final_confidence))

    def save_model(self):
        """Zapisz wytrenowany model"""
        if not self.model:
            logger.warning("No model to save")
            return False

        try:
            model_data = {
                'model': self.model,
                'scaler': self.scaler,
                'feature_columns': self.feature_columns,
                'training_date': self.training_date or datetime.now().isoformat(),
                'model_version': self.model_version,
                'model_type': type(self.model).__name__
            }

            # Stwórz katalog jeśli nie istnieje
            os.makedirs(os.path.dirname(self.model_path), exist_ok=True)

            # Zapisz model
            joblib.dump(model_data, self.model_path)
            logger.info(f"✅ Model saved successfully to {self.model_path}")
            return True

        except Exception as e:
            logger.error(f"❌ Error saving model: {e}")
            return False

    def load_model(self):
        """Wczytaj zapisany model"""
        try:
            if not os.path.exists(self.model_path):
                raise FileNotFoundError(f"Model file not found: {self.model_path}")

            model_data = joblib.load(self.model_path)

            self.model = model_data['model']
            self.scaler = model_data['scaler']
            self.feature_columns = model_data['feature_columns']
            self.training_date = model_data.get('training_date', 'unknown')
            self.model_version = model_data.get('model_version', '1.0')

            logger.info(f"✅ Model loaded: {model_data.get('model_type', 'Unknown')} trained on {self.training_date}")
            return True

        except Exception as e:
            logger.error(f"❌ Error loading model: {e}")
            return False

    def get_model_info(self):
        """Informacje o modelu"""
        return {
            'model_loaded': self.model is not None,
            'model_type': type(self.model).__name__ if self.model else None,
            'training_date': self.training_date,
            'version': self.model_version,
            'features_count': len(self.feature_columns),
            'model_path': self.model_path
        }
