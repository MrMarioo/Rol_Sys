import numpy as np
from scipy import ndimage
from sklearn.cluster import DBSCAN
import logging
from .utils import convert_numpy_types, safe_float, safe_int, safe_bool


logger = logging.getLogger(__name__)

class AdvancedNDVIAnalyzer:
    def __init__(self):
        self.grid_size = 10  # 10x10 grid dla 100 punktów NDVI

    def analyze_advanced_ndvi(self, ndvi_data):
        """
        Zaawansowana analiza NDVI wykraczająca poza podstawowe statystyki
        Wykorzystuje 100-punktowy grid do analizy przestrzennej
        """
        try:
            # Konwersja do grid 10x10
            ndvi_values = []
            for data_point in ndvi_data:
                if 'data' in data_point and 'ndvi_values' in data_point['data']:
                    ndvi_values.extend(data_point['data']['ndvi_values'])

            if len(ndvi_values) == 0:
                return self._empty_result()

            # Podstawowe statystyki
            basic_stats = self._calculate_basic_stats(ndvi_values)

            # Analiza przestrzenna (jeśli mamy dokładnie 100 punktów)
            spatial_analysis = {}
            if len(ndvi_values) >= 100:
                ndvi_grid = np.array(ndvi_values[:100]).reshape(10, 10)
                spatial_analysis = self._analyze_spatial_patterns(ndvi_grid)

            # Analiza trendów czasowych
            temporal_analysis = self._analyze_temporal_trends(ndvi_data)

            # Wykrywanie anomalii
            anomaly_analysis = self._detect_ndvi_anomalies(ndvi_values)

            # Klasyfikacja stanu zdrowotnego
            health_classification = self._classify_vegetation_health(basic_stats, spatial_analysis)
            result = {
                'basic_stats': basic_stats,
                'spatial_analysis': spatial_analysis,
                'temporal_analysis': temporal_analysis,
                'anomaly_analysis': anomaly_analysis,
                'health_classification': health_classification,
                'recommendations': self._generate_ndvi_recommendations(
                    basic_stats, spatial_analysis, anomaly_analysis
                )
            }

            return convert_numpy_types(result)

        except Exception as e:
            logger.error(f"Błąd zaawansowanej analizy NDVI: {e}")
            return self._empty_result()

    def _calculate_basic_stats(self, ndvi_values):
        """Obliczanie podstawowych statystyk NDVI"""
        ndvi_array = np.array(ndvi_values)

        return {
            'mean': float(np.mean(ndvi_array)),
            'std': float(np.std(ndvi_array)),
            'min': float(np.min(ndvi_array)),
            'max': float(np.max(ndvi_array)),
            'median': float(np.median(ndvi_array)),
            'q25': float(np.percentile(ndvi_array, 25)),
            'q75': float(np.percentile(ndvi_array, 75)),
            'coefficient_of_variation': float(np.std(ndvi_array) / np.mean(ndvi_array)) if np.mean(ndvi_array) > 0 else 0
        }

    def _analyze_spatial_patterns(self, ndvi_grid):
        """
        Analiza wzorców przestrzennych w gridzie NDVI
        Implementuje Moran's I i wykrywanie klastrów
        """
        spatial_autocorr = self._calculate_spatial_autocorrelation(ndvi_grid)
        edge_effects = self._analyze_edge_effects(ndvi_grid)
        problem_clusters = self._detect_problem_clusters(ndvi_grid)
        gradient_analysis = self._analyze_spatial_gradients(ndvi_grid)

        return {
            'spatial_autocorrelation': spatial_autocorr,
            'edge_effects': edge_effects,
            'problem_clusters': problem_clusters,
            'gradient_analysis': gradient_analysis,
            'spatial_homogeneity': self._calculate_spatial_homogeneity(ndvi_grid)
        }

    def _calculate_spatial_autocorrelation(self, ndvi_grid):
        """
        Obliczanie autokorelacji przestrzennej (zmodyfikowane Moran's I)
        Dostosowane do aplikacji rolniczych
        """
        try:
            # Spłaszczenie grid dla łatwiejszych obliczeń
            flat_values = ndvi_grid.flatten()
            n = len(flat_values)

            if n < 10:
                return {'moran_i': 0.0, 'interpretation': 'insufficient_data'}

            # Tworzenie macierzy wag dla grid 10x10
            W = self._create_agricultural_weight_matrix()

            # Obliczanie Moran's I
            mean_ndvi = np.mean(flat_values)
            numerator = 0
            denominator = 0

            for i in range(n):
                for j in range(n):
                    if i != j:
                        numerator += W[i,j] * (flat_values[i] - mean_ndvi) * (flat_values[j] - mean_ndvi)
                denominator += (flat_values[i] - mean_ndvi) ** 2

            W_sum = np.sum(W)
            moran_i = (n / W_sum) * (numerator / denominator) if denominator > 0 else 0

            # Interpretacja dla rolnictwa
            if moran_i > 0.3:
                interpretation = 'high_clustering'  # Czynniki systemowe
            elif moran_i < -0.1:
                interpretation = 'high_dispersion'  # Problemy losowe
            else:
                interpretation = 'moderate_pattern'  # Czynniki mieszane

            return {
                'moran_i': float(moran_i),
                'interpretation': interpretation,
                'confidence': 'high' if n >= 50 else 'medium'
            }

        except Exception as e:
            logger.error(f"Błąd obliczania autokorelacji: {e}")
            return {'moran_i': 0.0, 'interpretation': 'error'}

    def _create_agricultural_weight_matrix(self):
        """
        Macierz wag dostosowana do wzorców rolniczych
        Uwzględnia typowe odległości sprzętu rolniczego
        """
        n = 100  # 10x10 grid
        W = np.zeros((n, n))

        for i in range(10):
            for j in range(10):
                idx1 = i * 10 + j

                for ii in range(10):
                    for jj in range(10):
                        idx2 = ii * 10 + jj

                        if idx1 != idx2:
                            # Odległość Manhattan w gridzie
                            dist = abs(i - ii) + abs(j - jj)

                            # Wagi specyficzne dla rolnictwa
                            if dist == 1:  # Sąsiedzi bezpośredni
                                W[idx1, idx2] = 1.0
                            elif dist == 2:  # Sąsiedzi drugiego rzędu
                                W[idx1, idx2] = 0.5
                            elif dist <= 4:  # Odległe punkty w zasięgu sprzętu
                                W[idx1, idx2] = 0.2
                            else:
                                W[idx1, idx2] = 0.0

        # Normalizacja wierszowa
        row_sums = W.sum(axis=1)
        W = np.divide(W, row_sums[:, np.newaxis], out=np.zeros_like(W), where=row_sums[:, np.newaxis]!=0)

        return W

    def _analyze_edge_effects(self, ndvi_grid):
        """Analiza efektów brzegowych w polu"""
        # Wyodrębnienie brzegów
        edges = np.zeros_like(ndvi_grid)
        edges[0, :] = ndvi_grid[0, :]  # górny brzeg
        edges[-1, :] = ndvi_grid[-1, :] # dolny brzeg
        edges[:, 0] = ndvi_grid[:, 0]  # lewy brzeg
        edges[:, -1] = ndvi_grid[:, -1] # prawy brzeg

        # Środek pola
        center = ndvi_grid[2:8, 2:8]

        edge_mean = np.mean(edges[edges > 0])
        center_mean = np.mean(center)

        edge_effect_ratio = edge_mean / center_mean if center_mean > 0 else 1.0

        return {
            'edge_mean_ndvi': safe_float(edge_mean),           # ZMIENIONE
            'center_mean_ndvi': safe_float(center_mean),       # ZMIENIONE
            'edge_effect_ratio': safe_float(edge_effect_ratio), # ZMIENIONE
            'has_edge_effects': safe_bool(edge_effect_ratio < 0.9)  # ZMIENIONE
        }

    def _detect_problem_clusters(self, ndvi_grid):
        """Wykrywanie klastrów problemowych używając DBSCAN"""
        try:
            # Znajdowanie punktów o niskim NDVI
            threshold = np.mean(ndvi_grid) - np.std(ndvi_grid)
            low_ndvi_mask = ndvi_grid < threshold

            # Współrzędne punktów problemowych
            problem_coords = np.column_stack(np.where(low_ndvi_mask))

            if len(problem_coords) < 2:
                return {
                    'cluster_count': 0,
                    'clusters': [],
                    'total_problem_area': 0.0
                }

            # Clustering DBSCAN
            clustering = DBSCAN(eps=1.5, min_samples=2).fit(problem_coords)

            clusters = []
            for cluster_id in set(clustering.labels_):
                if cluster_id != -1:  # Pomijamy szum (-1)
                    cluster_points = problem_coords[clustering.labels_ == cluster_id]

                    clusters.append({
                        'cluster_id': safe_int(cluster_id),
                        'size': safe_int(len(cluster_points)),
                        'center': [safe_float(np.mean(cluster_points[:, 0])),
                                 safe_float(np.mean(cluster_points[:, 1]))],
                        'severity': self._assess_cluster_severity(cluster_points, ndvi_grid)
                    })

            return {
                'cluster_count': safe_int(len(clusters)),
                'clusters': clusters,
                'total_problem_area': safe_float(len(problem_coords) / 100.0)
            }

        except Exception as e:
            logger.error(f"Błąd wykrywania klastrów: {e}")
            return {'cluster_count': 0, 'clusters': [], 'total_problem_area': 0.0}

    def _assess_cluster_severity(self, cluster_points, ndvi_grid):
        """Ocena dotkliwości klastra problemowego"""
        cluster_ndvi_values = [ndvi_grid[int(point[0]), int(point[1])] for point in cluster_points]
        mean_cluster_ndvi = np.mean(cluster_ndvi_values)

        if mean_cluster_ndvi < 0.3:
            return 'critical'
        elif mean_cluster_ndvi < 0.5:
            return 'severe'
        elif mean_cluster_ndvi < 0.6:
            return 'moderate'
        else:
            return 'mild'

    def _analyze_spatial_gradients(self, ndvi_grid):
        """Analiza gradientów przestrzennych"""
        # Gradient w kierunku X i Y
        grad_x = np.gradient(ndvi_grid, axis=1)
        grad_y = np.gradient(ndvi_grid, axis=0)

        # Magnitude gradientu
        grad_magnitude = np.sqrt(grad_x**2 + grad_y**2)

        return {
            'mean_gradient': float(np.mean(grad_magnitude)),
            'max_gradient': float(np.max(grad_magnitude)),
            'gradient_std': float(np.std(grad_magnitude)),
            'high_gradient_areas': int(np.sum(grad_magnitude > np.percentile(grad_magnitude, 90)))
        }

    def _calculate_spatial_homogeneity(self, ndvi_grid):
        """Obliczanie jednorodności przestrzennej"""
        # Coefficient of variation jako miara homogeniczności
        cv = np.std(ndvi_grid) / np.mean(ndvi_grid) if np.mean(ndvi_grid) > 0 else 0

        # Interpretacja
        if cv < 0.1:
            homogeneity = 'very_high'
        elif cv < 0.2:
            homogeneity = 'high'
        elif cv < 0.3:
            homogeneity = 'moderate'
        else:
            homogeneity = 'low'

        return {
            'coefficient_of_variation': float(cv),
            'homogeneity_level': homogeneity
        }

    def _analyze_temporal_trends(self, ndvi_data):
        """Analiza trendów czasowych w danych NDVI"""
        if len(ndvi_data) < 2:
            return {'trend': 'insufficient_data', 'trend_strength': 0.0}

        try:
            # Sortowanie po dacie
            sorted_data = sorted(ndvi_data, key=lambda x: x.get('collection_date', ''))

            # Wyciągnięcie średnich NDVI dla każdego pomiaru
            dates = []
            mean_ndvi_values = []

            for data_point in sorted_data:
                if 'data' in data_point and 'ndvi_values' in data_point['data']:
                    ndvi_values = data_point['data']['ndvi_values']
                    mean_ndvi = np.mean(ndvi_values)
                    mean_ndvi_values.append(mean_ndvi)
                    dates.append(data_point.get('collection_date', ''))

            if len(mean_ndvi_values) < 2:
                return {'trend': 'insufficient_data', 'trend_strength': 0.0}

            # Prosty trend liniowy
            x = np.arange(len(mean_ndvi_values))
            slope = np.polyfit(x, mean_ndvi_values, 1)[0]

            # Klasyfikacja trendu
            if slope > 0.01:
                trend = 'increasing'
            elif slope < -0.01:
                trend = 'decreasing'
            else:
                trend = 'stable'

            return {
                'trend': trend,
                'trend_strength': float(abs(slope)),
                'latest_ndvi': float(mean_ndvi_values[-1]),
                'ndvi_change': float(mean_ndvi_values[-1] - mean_ndvi_values[0]) if len(mean_ndvi_values) > 1 else 0.0
            }

        except Exception as e:
            logger.error(f"Błąd analizy trendów czasowych: {e}")
            return {'trend': 'error', 'trend_strength': 0.0}

    def _detect_ndvi_anomalies(self, ndvi_values):
        """Wykrywanie anomalii w wartościach NDVI"""
        if len(ndvi_values) < 10:
            return {'anomaly_count': 0, 'anomaly_percentage': 0.0, 'anomaly_severity': 'none'}

        ndvi_array = np.array(ndvi_values)

        # Wykrywanie outlierów używając IQR
        q1 = np.percentile(ndvi_array, 25)
        q3 = np.percentile(ndvi_array, 75)
        iqr = q3 - q1

        lower_bound = q1 - 1.5 * iqr
        upper_bound = q3 + 1.5 * iqr

        anomalies = (ndvi_array < lower_bound) | (ndvi_array > upper_bound)
        anomaly_count = int(np.sum(anomalies))
        anomaly_percentage = (anomaly_count / len(ndvi_values)) * 100

        # Ocena dotkliwości anomalii
        if anomaly_percentage > 20:
            severity = 'high'
        elif anomaly_percentage > 10:
            severity = 'medium'
        elif anomaly_percentage > 5:
            severity = 'low'
        else:
            severity = 'none'

        return {
            'anomaly_count': anomaly_count,
            'anomaly_percentage': float(anomaly_percentage),
            'anomaly_severity': severity,
            'lower_threshold': float(lower_bound),
            'upper_threshold': float(upper_bound)
        }

    def _classify_vegetation_health(self, basic_stats, spatial_analysis):
        """Klasyfikacja stanu zdrowotnego roślinności"""
        mean_ndvi = basic_stats['mean']
        cv = basic_stats['coefficient_of_variation']

        # Podstawowa klasyfikacja NDVI
        if mean_ndvi > 0.7:
            base_health = 'excellent'
        elif mean_ndvi > 0.5:
            base_health = 'good'
        elif mean_ndvi > 0.3:
            base_health = 'poor'
        else:
            base_health = 'critical'

        # Modyfikacja na podstawie jednorodności
        if cv > 0.3:  # Niska jednorodność
            if base_health == 'excellent':
                base_health = 'good'
            elif base_health == 'good':
                base_health = 'poor'

        # Modyfikacja na podstawie analizy przestrzennej
        if spatial_analysis and spatial_analysis.get('problem_clusters', {}).get('cluster_count', 0) > 2:
            if base_health in ['excellent', 'good']:
                base_health = 'moderate_with_issues'

        return {
            'overall_health': base_health,
            'health_score': self._calculate_health_score(basic_stats, spatial_analysis),
            'confidence': 'high' if len(basic_stats) > 50 else 'medium'
        }

    def _calculate_health_score(self, basic_stats, spatial_analysis):
        """Obliczanie numerycznego wskaźnika zdrowia (0-100)"""
        # Podstawowy score na podstawie NDVI
        ndvi_score = min(basic_stats['mean'] / 0.8 * 70, 70)  # Max 70 punktów za NDVI

        # Bonus za jednorodność
        cv = basic_stats['coefficient_of_variation']
        homogeneity_score = max(0, 20 * (1 - cv / 0.5))  # Max 20 punktów

        # Kara za problemy przestrzenne
        spatial_penalty = 0
        if spatial_analysis:
            problem_area = spatial_analysis.get('problem_clusters', {}).get('total_problem_area', 0)
            spatial_penalty = min(problem_area * 20, 15)  # Max 15 punktów kary

        # Bonus za wysoką stabilność
        stability_bonus = 10 if cv < 0.1 else 0

        total_score = ndvi_score + homogeneity_score - spatial_penalty + stability_bonus
        return min(max(total_score, 0), 100)

    def _generate_ndvi_recommendations(self, basic_stats, spatial_analysis, anomaly_analysis):
        """Generowanie rekomendacji na podstawie analizy NDVI"""
        recommendations = []

        mean_ndvi = basic_stats['mean']

        # Rekomendacje podstawowe
        if mean_ndvi < 0.5:
            recommendations.append({
                'type': 'fertilization',
                'priority': 'high',
                'action': 'Rozważ nawożenie azotowe - niski NDVI wskazuje na problemy z wzrostem',
                'urgency_days': 3
            })

        # Rekomendacje przestrzenne
        if spatial_analysis and spatial_analysis.get('problem_clusters', {}).get('cluster_count', 0) > 0:
            cluster_count = spatial_analysis['problem_clusters']['cluster_count']
            recommendations.append({
                'type': 'targeted_intervention',
                'priority': 'medium',
                'action': f'Wykryto {cluster_count} obszarów problemowych - przeprowadź ukierunkowaną interwencję',
                'urgency_days': 7
            })

        # Rekomendacje dla efektów brzegowych
        if spatial_analysis and spatial_analysis.get('edge_effects', {}).get('has_edge_effects', False):
            recommendations.append({
                'type': 'edge_management',
                'priority': 'low',
                'action': 'Wykryto efekty brzegowe - rozważ dostosowanie praktyk na brzegach pola',
                'urgency_days': 14
            })

        # Rekomendacje dla anomalii
        if anomaly_analysis['anomaly_severity'] in ['medium', 'high']:
            recommendations.append({
                'type': 'anomaly_investigation',
                'priority': 'medium' if anomaly_analysis['anomaly_severity'] == 'medium' else 'high',
                'action': f'Wysokiy poziom anomalii ({anomaly_analysis["anomaly_percentage"]:.1f}%) - przeprowadź szczegółową inspekcję',
                'urgency_days': 5 if anomaly_analysis['anomaly_severity'] == 'high' else 10
            })

        return recommendations

    def _empty_result(self):
        """Pusty wynik w przypadku błędu"""
        return {
            'basic_stats': {},
            'spatial_analysis': {},
            'temporal_analysis': {},
            'anomaly_analysis': {},
            'health_classification': {},
            'recommendations': []
        }
