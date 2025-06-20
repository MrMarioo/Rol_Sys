import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import mean_absolute_error, r2_score
import joblib
import logging
import os
from datetime import datetime

logger = logging.getLogger(__name__)

class EnhancedPlantGrowthPredictor:
    def __init__(self):
        self.models = {}
        self.model_weights = {}
        self.performance_history = {}
        self.feature_names = [
            'ndvi_mean', 'ndvi_std', 'ndvi_min', 'ndvi_max',
            'soil_moisture_mean', 'soil_moisture_std',
            'temp_avg', 'temp_max', 'temp_min',
            'humidity_avg', 'rainfall_sum',
            'days_since_planting', 'growth_stage',
            'spatial_autocorr', 'ndvi_trend', 'anomaly_count',
            'cluster_count', 'edge_effect_ratio'
        ]

        # Parametry adaptacyjne oparte na wiedzy dziedzinowej rolniczej
        self.rf_params = {
            'n_estimators': 150,
            'max_depth': 12,
            'min_samples_split': 5,
            'min_samples_leaf': 2,
            'max_features': 'sqrt',
            'bootstrap': True,
            'random_state': 42,
            'n_jobs': -1
        }

        self.gb_params = {
            'n_estimators': 150,
            'max_depth': 8,
            'learning_rate': 0.1,
            'subsample': 0.8,
            'validation_fraction': 0.1,
            'n_iter_no_change': 10,
            'random_state': 42
        }

        self.model_path = '/app/models/'
        os.makedirs(self.model_path, exist_ok=True)

    def load_or_train(self):
        """
        Strategia inicjalizacji zapewniająca czasy odpowiedzi poniżej sekundy
        Alternatywa dla leniwego ładowania, które wprowadza nieprzewidywalne opóźnienia
        """
        try:
            # Próba załadowania istniejących modeli
            if self._load_existing_models():
                logger.info("Załadowano istniejące modele zespołowe")
                return True
            else:
                # Trenowanie nowych modeli z syntetycznymi danymi
                logger.info("Trenowanie nowych modeli zespołowych")
                return self._train_with_synthetic_data()
        except Exception as e:
            logger.error(f"Błąd inicjalizacji modeli: {e}")
            return False

    def _load_existing_models(self):
        """Ładowanie wytrenowanych modeli z dysku"""
        try:
            rf_path = os.path.join(self.model_path, 'rf_model.joblib')
            gb_path = os.path.join(self.model_path, 'gb_model.joblib')
            weights_path = os.path.join(self.model_path, 'model_weights.joblib')

            if all(os.path.exists(p) for p in [rf_path, gb_path, weights_path]):
                self.models['rf'] = joblib.load(rf_path)
                self.models['gb'] = joblib.load(gb_path)
                self.model_weights = joblib.load(weights_path)
                return True
            return False
        except Exception as e:
            logger.error(f"Błąd ładowania modeli: {e}")
            return False

    def _train_with_synthetic_data(self):
        """Trenowanie modeli z syntetycznymi danymi rolniczymi"""
        try:
            # Generowanie syntetycznych danych treningowych
            X_train, y_train = self._generate_synthetic_training_data(1000)

            # Trenowanie Random Forest
            self.models['rf'] = RandomForestRegressor(**self.rf_params)
            self.models['rf'].fit(X_train, y_train)

            # Trenowanie Gradient Boosting
            self.models['gb'] = GradientBoostingRegressor(**self.gb_params)
            self.models['gb'].fit(X_train, y_train)

            # Obliczanie wag na podstawie wydajności
            self._calculate_adaptive_weights(X_train, y_train)

            # Zapisywanie modeli
            self._save_models()

            logger.info("Pomyślnie wytrenowano i zapisano modele zespołowe")
            return True

        except Exception as e:
            logger.error(f"Błąd trenowania modeli: {e}")
            return False

    def _generate_synthetic_training_data(self, n_samples):
        """Generowanie realistycznych danych treningowych dla rolnictwa"""
        np.random.seed(42)

        # Generowanie cech podstawowych
        ndvi_mean = np.random.beta(2, 2, n_samples) * 0.8 + 0.1  # NDVI 0.1-0.9
        ndvi_std = np.random.exponential(0.1, n_samples)

        # Korelowane cechy środowiskowe
        temp_avg = np.random.normal(22, 5, n_samples)
        rainfall = np.random.gamma(2, 10, n_samples)
        soil_moisture = np.clip(np.random.normal(0.4, 0.15, n_samples), 0.1, 0.8)

        # Cechy czasowe
        days_since_planting = np.random.randint(1, 120, n_samples)
        growth_stage = np.clip(days_since_planting // 30, 0, 3)

        # Nowe cechy przestrzenne (symulowane)
        spatial_autocorr = np.random.normal(0.3, 0.2, n_samples)
        cluster_count = np.random.poisson(2, n_samples)

        X = np.column_stack([
            ndvi_mean, ndvi_std, ndvi_mean - ndvi_std, ndvi_mean + ndvi_std,
            soil_moisture, np.random.normal(0.05, 0.02, n_samples),
            temp_avg, temp_avg + 5, temp_avg - 3,
            np.random.normal(65, 10, n_samples), rainfall,
            days_since_planting, growth_stage,
            spatial_autocorr, np.random.normal(0.02, 0.01, n_samples),
            np.random.poisson(1, n_samples), cluster_count,
            np.random.beta(2, 3, n_samples)
        ])

        # Generowanie target variable (biomass prediction)
        # Złożona funkcja uwzględniająca interakcje między cechami
        y = (ndvi_mean * 10 +
             soil_moisture * 3 +
             (temp_avg - 15) * 0.2 +
             np.log1p(rainfall) * 0.5 +
             spatial_autocorr * 2 +
             np.random.normal(0, 0.5, n_samples))  # szum

        y = np.clip(y, 0, 15)  # Realistyczny zakres biomasy

        return X, y

    def _calculate_adaptive_weights(self, X, y):
        """
        Dynamiczne obliczanie wag na podstawie wydajności modeli
        Wykorzystuje walidację krzyżową dla objektywnej oceny
        """
        # Cross-validation dla objektywnej oceny
        rf_scores = cross_val_score(self.models['rf'], X, y, cv=5, scoring='r2')
        gb_scores = cross_val_score(self.models['gb'], X, y, cv=5, scoring='r2')

        rf_performance = np.mean(rf_scores)
        gb_performance = np.mean(gb_scores)

        logger.info(f"RF R² score: {rf_performance:.3f}")
        logger.info(f"GB R² score: {gb_performance:.3f}")

        # Obliczanie wag na podstawie względnej wydajności
        total_performance = rf_performance + gb_performance

        if total_performance > 0:
            rf_weight = rf_performance / total_performance
            gb_weight = gb_performance / total_performance
        else:
            # Zapasowe wagi
            rf_weight, gb_weight = 0.6, 0.4

        # Normalizacja wag
        total_weight = rf_weight + gb_weight
        self.model_weights = {
            'rf': rf_weight / total_weight,
            'gb': gb_weight / total_weight
        }

        logger.info(f"Adaptive weights - RF: {self.model_weights['rf']:.3f}, GB: {self.model_weights['gb']:.3f}")

    def predict_ensemble(self, features):
        """Predykcja zespołowa z adaptacyjnymi wagami"""
        try:
            if not self.models or not self.model_weights:
                return None

            # Konwersja do numpy array jeśli potrzeba
            if isinstance(features, list):
                features = np.array(features).reshape(1, -1)
            elif len(features.shape) == 1:
                features = features.reshape(1, -1)

            # Predykcje z poszczególnych modeli
            rf_pred = self.models['rf'].predict(features)
            gb_pred = self.models['gb'].predict(features)

            # Zespołowa predykcja z wagami
            ensemble_pred = (self.model_weights['rf'] * rf_pred +
                           self.model_weights['gb'] * gb_pred)

            # Metryki pewności
            rf_confidence = np.abs(rf_pred - ensemble_pred)
            gb_confidence = np.abs(gb_pred - ensemble_pred)
            confidence_score = 1.0 - np.mean([rf_confidence, gb_confidence]) / np.std([rf_pred, gb_pred])
            confidence_score = np.clip(confidence_score, 0.0, 1.0)

            return {
                'growth_prediction': float(ensemble_pred[0]),
                'rf_prediction': float(rf_pred[0]),
                'gb_prediction': float(gb_pred[0]),
                'confidence_score': float(confidence_score[0]) if hasattr(confidence_score, '__len__') else float(confidence_score),
                'model_weights': self.model_weights.copy()
            }

        except Exception as e:
            logger.error(f"Błąd predykcji zespołowej: {e}")
            return None

    def get_feature_importance(self):
        """Pobieranie ważności cech z modeli zespołowych"""
        if not self.models:
            return {}

        try:
            rf_importance = self.models['rf'].feature_importances_
            gb_importance = self.models['gb'].feature_importances_

            # Ważona kombinacja ważności cech
            combined_importance = (self.model_weights['rf'] * rf_importance +
                                 self.model_weights['gb'] * gb_importance)

            feature_importance = {}
            for i, name in enumerate(self.feature_names):
                if i < len(combined_importance):
                    feature_importance[name] = float(combined_importance[i])

            return feature_importance

        except Exception as e:
            logger.error(f"Błąd pobierania ważności cech: {e}")
            return {}

    def _save_models(self):
        """Zapisywanie wytrenowanych modeli"""
        try:
            joblib.dump(self.models['rf'], os.path.join(self.model_path, 'rf_model.joblib'))
            joblib.dump(self.models['gb'], os.path.join(self.model_path, 'gb_model.joblib'))
            joblib.dump(self.model_weights, os.path.join(self.model_path, 'model_weights.joblib'))
            logger.info("Modele zapisane pomyślnie")
        except Exception as e:
            logger.error(f"Błąd zapisywania modeli: {e}")

    def load_fallback_model(self):
        """Prosty model zapasowy w przypadku błędu głównych modeli"""
        from sklearn.linear_model import LinearRegression

        # Bardzo prosty model jako fallback
        X_simple, y_simple = self._generate_synthetic_training_data(100)

        self.fallback_model = LinearRegression()
        self.fallback_model.fit(X_simple[:, :4], y_simple)  # Tylko podstawowe cechy NDVI

        logger.info("Załadowano zapasowy model liniowy")

    def predict_fallback(self, features):
        """Predykcja zapasowa z prostego modelu"""
        try:
            if hasattr(self, 'fallback_model'):
                if isinstance(features, list):
                    features = np.array(features)

                # Użyj tylko pierwszych 4 cech (NDVI)
                simple_features = features[:4].reshape(1, -1)
                prediction = self.fallback_model.predict(simple_features)

                return {
                    'growth_prediction': float(prediction[0]),
                    'confidence_score': 0.5,  # Niska pewność dla modelu zapasowego
                    'model_type': 'fallback_linear'
                }
            return None
        except Exception as e:
            logger.error(f"Błąd fallback prediction: {e}")
            return None
