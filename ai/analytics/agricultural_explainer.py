import logging
from typing import Dict, List, Any

logger = logging.getLogger(__name__)

class AgriculturalExplainer:
    def __init__(self):
        # Wiedza dziedzinowa rolnicza dla interpretacji wyników uczenia maszynowego
        self.feature_interpretations = {
            'ndvi_mean': {
                'low': 'Niska aktywność fotosyntezy - możliwy niedobór nawożenia lub stres',
                'medium': 'Umiarkowana aktywność wegetatywna - pole wymaga monitoringu',
                'high': 'Optymalna aktywność wegetatywna - zdrowa roślinność',
                'thresholds': {'low': 0.4, 'medium': 0.7}
            },
            'ndvi_std': {
                'low': 'Jednorodne pole - równomierne zarządzanie',
                'high': 'Niejednorodne pole - rozważ zarządzanie strefowe',
                'thresholds': {'low': 0.1, 'high': 0.2}
            },
            'soil_moisture_mean': {
                'low': 'Niska wilgotność gleby - ryzyko stresu wodnego',
                'optimal': 'Optymalna wilgotność gleby',
                'high': 'Wysoka wilgotność - ryzyko problemów z napowietrzaniem',
                'thresholds': {'low': 0.3, 'optimal_min': 0.4, 'optimal_max': 0.6}
            },
            'temp_avg': {
                'cold': 'Niska temperatura może spowalniać wzrost',
                'optimal': 'Optymalna temperatura dla wzrostu',
                'hot': 'Wysoka temperatura - ryzyko stresu cieplnego',
                'thresholds': {'cold': 15, 'optimal_min': 18, 'optimal_max': 28}
            },
            'spatial_autocorr': {
                'high': 'Silne grupowanie przestrzenne - czynniki systemowe',
                'low': 'Słabe grupowanie - problemy lokalizowane',
                'thresholds': {'low': 0.1, 'high': 0.3}
            }
        }

        self.recommendation_templates = {
            'fertilization': {
                'nitrogen': 'Na podstawie analizy NDVI ({ndvi_mean:.2f}) zalecane nawożenie azotowe {dose}kg N/ha',
                'phosphorus': 'Faza rozwoju wymaga wsparcia fosforowego - {dose}kg P2O5/ha',
                'potassium': 'Dla optymalnej jakości plonu zastosuj {dose}kg K2O/ha'
            },
            'irrigation': {
                'immediate': 'Pilne nawadnianie - wilgotność gleby {moisture}% poniżej optimum',
                'planned': 'Planowe nawadnianie w ciągu {days} dni',
                'monitoring': 'Intensyfikacja monitorowania wilgotności gleby'
            },
            'pest_management': {
                'inspection': 'Anomalie w NDVI sugerują możliwe problemy - przeprowadź inspekcję',
                'treatment': 'Wykryto wzorce sugerujące {pest_type} - rozważ leczenie'
            }
        }

        self.growth_stage_contexts = {
            0: {'name': 'kiełkowanie', 'key_factors': ['temp_avg', 'soil_moisture_mean']},
            1: {'name': 'wzrost wegetatywny', 'key_factors': ['ndvi_mean', 'nitrogen_level']},
            2: {'name': 'kwitnienie', 'key_factors': ['temp_avg', 'water_stress']},
            3: {'name': 'dojrzewanie', 'key_factors': ['water_management', 'disease_pressure']}
        }

    def explain_prediction(self, prediction_result: Dict, feature_importance: Dict, field_context: Dict) -> Dict:
        """
        Konwersja technicznego wyniku uczenia maszynowego na praktyczne rekomendacje rolnicze
        z pełnym wyjaśnieniem rozumowania dla zrozumienia rolnika
        """
        try:
            explanations = []

            # Wyjaśnienie głównej predykcji
            growth_level = prediction_result.get('growth_prediction', 0)
            confidence = prediction_result.get('confidence_score', 0.5)

            base_explanation = self._generate_base_explanation(growth_level, confidence)
            explanations.append(base_explanation)

            # Wyjaśnienia oparte na cechach
            sorted_features = sorted(feature_importance.items(), key=lambda x: x[1], reverse=True)

            for feature_name, importance in sorted_features[:5]:  # 5 najważniejszych czynników
                if importance > 0.1:  # Tylko znaczące czynniki
                    feature_explanation = self._explain_feature_impact(
                        feature_name, importance, field_context
                    )
                    if feature_explanation:
                        explanations.append(feature_explanation)

            # Kontekstowe rekomendacje
            recommendations = self._generate_contextual_recommendations(
                prediction_result, feature_importance, field_context
            )

            return {
                'explanations': explanations,
                'recommendations': recommendations,
                'confidence_factors': self._explain_confidence_factors(prediction_result),
                'next_steps': self._suggest_next_steps(prediction_result, field_context),
                'interpretation_summary': self._create_summary(explanations, recommendations)
            }

        except Exception as e:
            logger.error(f"Błąd wyjaśniania predykcji: {e}")
            return self._empty_explanation()

    def _generate_base_explanation(self, growth_level: float, confidence: float) -> Dict:
        """Generowanie podstawowego wyjaśnienia predykcji"""
        if growth_level > 8:
            status = 'excellent'
            message = 'Przewidywany doskonały wzrost biomasy'
        elif growth_level > 6:
            status = 'good'
            message = 'Przewidywany dobry wzrost biomasy'
        elif growth_level > 4:
            status = 'moderate'
            message = 'Przewidywany umiarkowany wzrost biomasy'
        else:
            status = 'poor'
            message = 'Przewidywany słaby wzrost biomasy - wymagana interwencja'

        confidence_text = self._interpret_confidence(confidence)

        return {
            'type': 'base_prediction',
            'status': status,
            'message': message,
            'growth_value': growth_level,
            'confidence': confidence,
            'confidence_text': confidence_text
        }

    def _explain_feature_impact(self, feature_name: str, importance: float, field_context: Dict) -> Dict:
        """Wyjaśnienie wpływu konkretnej cechy"""
        if feature_name not in self.feature_interpretations:
            return None

        feature_info = self.feature_interpretations[feature_name]
        current_value = field_context.get(feature_name, 0)

        # Klasyfikacja wartości cechy
        category = self._classify_feature_value(feature_name, current_value, feature_info)

        if category in feature_info:
            interpretation = feature_info[category]
        else:
            interpretation = f"Wartość {feature_name}: {current_value:.2f}"

        # Kontekst sezonowy/fazowy
        growth_stage = field_context.get('growth_stage', 1)
        seasonal_context = self._get_seasonal_context(feature_name, growth_stage)

        return {
            'type': 'feature_explanation',
            'feature': feature_name,
            'importance': importance,
            'current_value': current_value,
            'category': category,
            'interpretation': interpretation,
            'seasonal_context': seasonal_context,
            'actionability': self._assess_actionability(feature_name)
        }

    def _classify_feature_value(self, feature_name: str, value: float, feature_info: Dict) -> str:
        """Klasyfikacja wartości cechy do kategorii interpretacyjnych"""
        thresholds = feature_info.get('thresholds', {})

        if feature_name == 'ndvi_mean':
            if value < thresholds.get('low', 0.4):
                return 'low'
            elif value < thresholds.get('medium', 0.7):
                return 'medium'
            else:
                return 'high'

        elif feature_name == 'soil_moisture_mean':
            if value < thresholds.get('low', 0.3):
                return 'low'
            elif thresholds.get('optimal_min', 0.4) <= value <= thresholds.get('optimal_max', 0.6):
                return 'optimal'
            else:
                return 'high'

        elif feature_name == 'temp_avg':
            if value < thresholds.get('cold', 15):
                return 'cold'
            elif thresholds.get('optimal_min', 18) <= value <= thresholds.get('optimal_max', 28):
                return 'optimal'
            else:
                return 'hot'

        elif feature_name == 'ndvi_std':
            if value < thresholds.get('low', 0.1):
                return 'low'
            elif value > thresholds.get('high', 0.2):
                return 'high'
            else:
                return 'medium'

        elif feature_name == 'spatial_autocorr':
            if value < thresholds.get('low', 0.1):
                return 'low'
            elif value > thresholds.get('high', 0.3):
                return 'high'
            else:
                return 'medium'

        return 'unknown'

    def _get_seasonal_context(self, feature_name: str, growth_stage: int) -> str:
        """Pobieranie kontekstu sezonowego dla cechy"""
        stage_info = self.growth_stage_contexts.get(growth_stage, {})
        stage_name = stage_info.get('name', 'nieznana faza')
        key_factors = stage_info.get('key_factors', [])

        if feature_name in key_factors:
            return f"Kluczowy czynnik w fazie {stage_name}"
        else:
            return f"Wpływ w fazie {stage_name}"

    def _assess_actionability(self, feature_name: str) -> Dict:
        """Ocena możliwości działania na daną cechę"""
        actionable_features = {
            'soil_moisture_mean': {'actionable': True, 'method': 'nawadnianie'},
            'ndvi_mean': {'actionable': True, 'method': 'nawożenie, ochrona roślin'},
            'temp_avg': {'actionable': False, 'method': 'czynnik zewnętrzny'},
            'spatial_autocorr': {'actionable': True, 'method': 'zarządzanie strefowe'}
        }

        return actionable_features.get(feature_name, {'actionable': False, 'method': 'nieznany'})

    def _generate_contextual_recommendations(self, prediction_result: Dict, feature_importance: Dict, field_context: Dict) -> List[Dict]:
        """Generowanie kontekstowych rekomendacji"""
        recommendations = []

        # Rekomendacje na podstawie głównej predykcji
        growth_prediction = prediction_result.get('growth_prediction', 0)

        if growth_prediction < 4:
            recommendations.append({
                'type': 'urgent_intervention',
                'priority': 'high',
                'action': 'Niskie przewidywania wzrostu wymagają pilnej interwencji',
                'specific_actions': self._suggest_growth_improvement_actions(feature_importance, field_context),
                'timeline': '1-3 dni'
            })

        # Rekomendacje na podstawie najważniejszych cech
        top_features = sorted(feature_importance.items(), key=lambda x: x[1], reverse=True)[:3]

        for feature_name, importance in top_features:
            if importance > 0.15:  # Tylko bardzo ważne cechy
                feature_recommendations = self._get_feature_specific_recommendations(
                    feature_name, field_context.get(feature_name, 0), field_context
                )
                recommendations.extend(feature_recommendations)

        # Sortowanie według priorytetu
        priority_order = {'high': 3, 'medium': 2, 'low': 1}
        recommendations.sort(key=lambda x: priority_order.get(x.get('priority', 'low'), 0), reverse=True)

        return recommendations[:5]  # Maksymalnie 5 najważniejszych rekomendacji

    def _suggest_growth_improvement_actions(self, feature_importance: Dict, field_context: Dict) -> List[str]:
        """Sugerowanie konkretnych działań dla poprawy wzrostu"""
        actions = []

        # Sprawdź najważniejsze czynniki
        if feature_importance.get('ndvi_mean', 0) > 0.2:
            ndvi_value = field_context.get('ndvi_mean', 0.5)
            if ndvi_value < 0.5:
                actions.append('Nawożenie azotowe 80-120 kg N/ha')

        if feature_importance.get('soil_moisture_mean', 0) > 0.15:
            moisture_value = field_context.get('soil_moisture_mean', 0.4)
            if moisture_value < 0.3:
                actions.append('Natychmiastowe nawadnianie 25-30mm')
            elif moisture_value > 0.7:
                actions.append('Poprawa drenażu, ograniczenie nawadniania')

        if feature_importance.get('temp_avg', 0) > 0.1:
            temp_value = field_context.get('temp_avg', 20)
            if temp_value > 30:
                actions.append('Mitygacja stresu cieplnego - zwiększenie nawodnień')

        return actions if actions else ['Szczegółowa diagnostyka wymagana']

    def _get_feature_specific_recommendations(self, feature_name: str, feature_value: float, field_context: Dict) -> List[Dict]:
        """Pobieranie rekomendacji specyficznych dla cechy"""
        recommendations = []

        if feature_name == 'ndvi_mean' and feature_value < 0.5:
            recommendations.append({
                'type': 'fertilization',
                'priority': 'high',
                'action': f'NDVI {feature_value:.2f} wskazuje na problemy wzrostu - rozważ nawożenie azotowe',
                'timeline': '3-5 dni'
            })

        elif feature_name == 'soil_moisture_mean':
            if feature_value < 0.3:
                recommendations.append({
                    'type': 'irrigation',
                    'priority': 'high',
                    'action': f'Krytycznie niska wilgotność gleby ({feature_value:.2f}) - natychmiastowe nawadnianie',
                    'timeline': '1-2 dni'
                })
            elif feature_value > 0.7:
                recommendations.append({
                    'type': 'drainage',
                    'priority': 'medium',
                    'action': f'Nadmierna wilgotność gleby ({feature_value:.2f}) - sprawdź drenowanie',
                    'timeline': '5-7 dni'
                })

        elif feature_name == 'spatial_autocorr' and feature_value > 0.3:
            recommendations.append({
                'type': 'zone_management',
                'priority': 'medium',
                'action': 'Wysokie grupowanie przestrzenne - rozważ zarządzanie strefowe',
                'timeline': '1-2 tygodnie'
            })

        return recommendations

    def _explain_confidence_factors(self, prediction_result: Dict) -> Dict:
        """Wyjaśnienie czynników wpływających na pewność predykcji"""
        confidence = prediction_result.get('confidence_score', 0.5)

        factors = []

        if confidence > 0.8:
            factors.append('Wysokie zgodność między modelami zespołowymi')
            factors.append('Wystarczająca ilość danych historycznych')
        elif confidence > 0.6:
            factors.append('Umiarkowana zgodność między modelami')
            factors.append('Standardowe warunki do predykcji')
        else:
            factors.append('Niska zgodność między modelami - wymagana ostrożność')
            factors.append('Ograniczone dane lub nietypowe warunki')

        # Model type influence
        model_type = prediction_result.get('model_type', 'ensemble')
        if model_type == 'fallback_linear':
            factors.append('Użyto modelu zapasowego - ograniczona dokładność')

        return {
            'confidence_level': self._interpret_confidence(confidence),
            'confidence_score': confidence,
            'factors': factors,
            'recommendation': self._confidence_recommendation(confidence)
        }

    def _interpret_confidence(self, confidence: float) -> str:
        """Interpretacja poziomu pewności"""
        if confidence > 0.8:
            return 'wysoka'
        elif confidence > 0.6:
            return 'średnia'
        elif confidence > 0.4:
            return 'niska'
        else:
            return 'bardzo niska'

    def _confidence_recommendation(self, confidence: float) -> str:
        """Rekomendacja na podstawie poziomu pewności"""
        if confidence > 0.8:
            return 'Można pewnie implementować rekomendacje'
        elif confidence > 0.6:
            return 'Rekomendacje prawdopodobnie trafne - zalecane zastosowanie'
        elif confidence > 0.4:
            return 'Rekomendacje wymagają weryfikacji - rozważ dodatkowe dane'
        else:
            return 'Niska pewność - zalecana konsultacja z ekspertem'

    def _suggest_next_steps(self, prediction_result: Dict, field_context: Dict) -> List[Dict]:
        """Sugerowanie następnych kroków"""
        next_steps = []

        confidence = prediction_result.get('confidence_score', 0.5)
        growth_prediction = prediction_result.get('growth_prediction', 0)

        # Podstawowe następne kroki
        if growth_prediction < 4:
            next_steps.append({
                'step': 'Pilna diagnostyka pola',
                'timeline': '1-2 dni',
                'priority': 'high'
            })

        if confidence < 0.6:
            next_steps.append({
                'step': 'Zbieranie dodatkowych danych (soil test, weather data)',
                'timeline': '3-5 dni',
                'priority': 'medium'
            })

        # Monitoring
        next_steps.append({
            'step': 'Kolejny pomiar NDVI',
            'timeline': '7-10 dni',
            'priority': 'medium'
        })

        # Długoterminowe
        next_steps.append({
            'step': 'Analiza efektywności zastosowanych działań',
            'timeline': '2-3 tygodnie',
            'priority': 'low'
        })

        return next_steps

    def _create_summary(self, explanations: List[Dict], recommendations: List[Dict]) -> str:
        """Tworzenie podsumowania interpretacji"""
        summary_parts = []

        # Główny status
        for exp in explanations:
            if exp.get('type') == 'base_prediction':
                summary_parts.append(f"Przewidywany wzrost: {exp.get('status', 'nieznany')}")
                break

        # Liczba rekomendacji
        high_priority_recs = len([r for r in recommendations if r.get('priority') == 'high'])
        if high_priority_recs > 0:
            summary_parts.append(f"{high_priority_recs} pilnych działań wymaganych")

        # Kluczowe czynniki
        important_features = [exp.get('feature') for exp in explanations
                            if exp.get('type') == 'feature_explanation' and exp.get('importance', 0) > 0.2]
        if important_features:
            summary_parts.append(f"Kluczowe czynniki: {', '.join(important_features[:2])}")

        return '. '.join(summary_parts) + '.'

    def _empty_explanation(self) -> Dict:
        """Puste wyjaśnienie w przypadku błędu"""
        return {
            'explanations': [],
            'recommendations': [],
            'confidence_factors': {'confidence_level': 'nieznana', 'factors': []},
            'next_steps': [],
            'interpretation_summary': 'Błąd interpretacji wyników'
        }
