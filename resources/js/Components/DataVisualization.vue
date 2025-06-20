<template>
    <div class="data-visualization">
        <!-- Table view -->
        <div v-if="visualizationType === 'table'" class="table-responsive">
            <table class="table-bordered table">
                <thead>
                <tr>
                    <th v-for="(value, key) in tableHeaders" :key="key">{{ value }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(row, index) in tableData" :key="index">
                    <td v-for="(value, key) in row" :key="key">{{ formatValue(value) }}</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Line chart -->
        <div v-else-if="visualizationType === 'line-chart'" class="chart-container">
            <v-chart class="chart" :option="lineChartOption" autoresize />
        </div>

        <!-- Bar chart -->
        <div v-else-if="visualizationType === 'bar-chart'" class="chart-container">
            <v-chart class="chart" :option="barChartOption" autoresize />
        </div>

        <!-- Pie chart -->
        <div v-else-if="visualizationType === 'pie-chart'" class="chart-container">
            <v-chart class="chart" :option="pieChartOption" autoresize />
        </div>

        <!-- Heatmap -->
        <div v-else-if="visualizationType === 'heatmap'" class="chart-container">
            <v-chart class="chart" :option="heatmapOption" autoresize />
        </div>

        <!-- NOWE - Summary cards dla niektórych typów -->
        <div v-else-if="visualizationType === 'summary'" class="summary-cards">
            <div class="row g-3">
                <div v-for="(item, key) in summaryData" :key="key" class="col-md-6 col-lg-4">
                    <div class="card text-center">
                        <div class="card-inner">
                            <h6 class="subtitle">{{ formatHeader(key) }}</h6>
                            <span class="amount">{{ formatValue(item.value) }}</span>
                            <span v-if="item.unit" class="unit">{{ item.unit }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- No suitable visualization -->
        <div v-else class="no-visualization">
            <div class="alert alert-info">
                <h6>Raw Data ({{ props.type }})</h6>
                <pre class="mt-3">{{ JSON.stringify(data, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart, HeatmapChart, BarChart, PieChart } from 'echarts/charts';
import {
    GridComponent,
    TooltipComponent,
    LegendComponent,
    MarkPointComponent,
    VisualMapComponent
} from 'echarts/components';
import VChart from 'vue-echarts';

// Register echarts components
use([
    CanvasRenderer,
    LineChart,
    HeatmapChart,
    BarChart,
    PieChart,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    MarkPointComponent,
    VisualMapComponent
]);

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    type: {
        type: String,
        required: true,
    },
});

const visualizationType = computed(() => {
    console.log('Data type:', props.type, 'Data:', props.data);

    switch (props.type) {
        // Istniejące typy
        case 'soil_moisture':
        case 'temperature':
            return hasArrayData('moisture_values') || hasArrayData('temperature_values') ? 'line-chart' : 'table';

        case 'ndvi':
            return hasArrayData('ndvi_values') ? 'line-chart' : (hasHeatmapData.value ? 'heatmap' : 'table');

        // NOWE typy z seedera
        case 'weather':
            return 'summary'; // Pogoda jako karty z kluczowymi danymi

        case 'soil_temperature':
            return hasArrayData('temperature_values') ? 'line-chart' : 'table';

        case 'fertilizer_application':
            return 'summary'; // Nawożenie jako karty

        case 'biomass_measurement':
            return 'summary'; // Biomasa jako karty z kluczowymi wskaźnikami

        case 'growth_stage':
            return 'summary'; // Faza wzrostu jako informacyjne karty

        case 'sunlight':
            return hasArrayData('daily_values') ? 'bar-chart' : 'summary'; // Nasłonecznienie jako wykres słupkowy lub karty

        default:
            return 'table';
    }
});

// Helper functions
const hasArrayData = (key) => {
    return props.data && Array.isArray(props.data[key]) && props.data[key].length > 0;
};

const hasTimeSeriesData = computed(() => {
    if (!props.data || !Array.isArray(props.data.readings)) return false;
    return props.data.readings.length > 0 && props.data.readings[0].timestamp !== undefined;
});

const hasHeatmapData = computed(() => {
    if (!props.data || !Array.isArray(props.data.grid)) return false;
    return props.data.grid.length > 0;
});

// NOWE - Summary data dla różnych typów
const summaryData = computed(() => {
    if (!props.data) return {};

    switch (props.type) {
        case 'weather':
            return {
                temperature: {
                    value: props.data.temperature_avg || props.data.temperature || 'N/A',
                    unit: '°C'
                },
                humidity: {
                    value: props.data.humidity || 'N/A',
                    unit: '%'
                },
                rainfall: {
                    value: props.data.rainfall || 0,
                    unit: 'mm'
                },
                wind_speed: {
                    value: props.data.wind_speed || 'N/A',
                    unit: 'km/h'
                },
                pressure: {
                    value: props.data.pressure || 'N/A',
                    unit: 'hPa'
                }
            };

        case 'fertilizer_application':
            return {
                fertilizer_type: {
                    value: props.data.fertilizer_type || 'Unknown',
                    unit: ''
                },
                amount_per_hectare: {
                    value: props.data.amount_per_hectare || 0,
                    unit: 'kg/ha'
                },
                total_amount: {
                    value: props.data.total_amount || 0,
                    unit: 'kg'
                },
                application_method: {
                    value: props.data.application_method || 'Unknown',
                    unit: ''
                }
            };

        case 'biomass_measurement':
            return {
                dry_biomass: {
                    value: props.data.dry_biomass_kg_per_ha || 0,
                    unit: 'kg/ha'
                },
                wet_biomass: {
                    value: props.data.wet_biomass_kg_per_ha || 0,
                    unit: 'kg/ha'
                },
                plant_height: {
                    value: props.data.plant_height_cm || 0,
                    unit: 'cm'
                },
                plant_density: {
                    value: props.data.plant_density_per_m2 || 0,
                    unit: 'plants/m²'
                }
            };

        case 'growth_stage':
            return {
                growth_stage: {
                    value: props.data.growth_stage || 'Unknown',
                    unit: ''
                },
                bbch_code: {
                    value: props.data.bbch_code || 'N/A',
                    unit: ''
                },
                days_since_planting: {
                    value: props.data.days_since_planting || 0,
                    unit: 'days'
                },
                estimated_harvest_days: {
                    value: props.data.estimated_harvest_days || 'N/A',
                    unit: 'days'
                }
            };

        case 'sunlight':
            return {
                sunshine_hours: {
                    value: props.data.sunshine_hours || 0,
                    unit: 'hours'
                },
                solar_radiation: {
                    value: props.data.solar_radiation_mj_m2 || 0,
                    unit: 'MJ/m²'
                },
                uv_index: {
                    value: props.data.uv_index || 0,
                    unit: ''
                },
                cloud_cover: {
                    value: props.data.cloud_cover_percent || 0,
                    unit: '%'
                }
            };

        default:
            return {};
    }
});

// Table data
const tableData = computed(() => {
    if (!props.data) return [];

    // Dla typów z wartościami array, pokaż te wartości
    if (props.type === 'soil_moisture' && props.data.moisture_values) {
        return props.data.moisture_values.map((value, index) => ({
            index: index + 1,
            moisture_value: value
        }));
    }

    if (props.type === 'ndvi' && props.data.ndvi_values) {
        return props.data.ndvi_values.map((value, index) => ({
            index: index + 1,
            ndvi_value: value
        }));
    }

    if (props.type === 'soil_temperature' && props.data.temperature_values) {
        return props.data.temperature_values.map((value, index) => ({
            index: index + 1,
            temperature: value
        }));
    }

    // Dla innych typów
    if (Array.isArray(props.data)) {
        return props.data;
    } else if (Array.isArray(props.data.readings)) {
        return props.data.readings;
    } else if (typeof props.data === 'object') {
        return [props.data];
    }

    return [];
});

const tableHeaders = computed(() => {
    if (tableData.value.length === 0) return {};

    const headers = {};
    Object.keys(tableData.value[0]).forEach((key) => {
        headers[key] = formatHeader(key);
    });

    return headers;
});

// Utilities
const formatHeader = (key) => {
    return key
        .replace(/_/g, ' ')
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

const formatValue = (value) => {
    if (value === null || value === undefined) return '-';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    if (typeof value === 'number') {
        return Number.isInteger(value) ? value : value.toFixed(2);
    }
    if (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}T/)) {
        try {
            return new Date(value).toLocaleString('en-US');
        } catch (e) {
            return value;
        }
    }
    return value;
};

// ROZSZERZONE - Line chart data
const lineChartData = computed(() => {
    if (props.type === 'soil_moisture' && props.data.moisture_values) {
        return {
            times: props.data.moisture_values.map((_, index) => `Point ${index + 1}`),
            values: props.data.moisture_values,
            valueKey: 'Moisture Level'
        };
    }

    if (props.type === 'ndvi' && props.data.ndvi_values) {
        return {
            times: props.data.ndvi_values.map((_, index) => `Point ${index + 1}`),
            values: props.data.ndvi_values,
            valueKey: 'NDVI Value'
        };
    }

    if (props.type === 'soil_temperature' && props.data.temperature_values) {
        return {
            times: props.data.temperature_values.map((_, index) => `Point ${index + 1}`),
            values: props.data.temperature_values,
            valueKey: 'Temperature (°C)'
        };
    }

    // Fallback dla starych formatów
    if (!props.data || !Array.isArray(props.data.readings)) return { times: [], values: [], valueKey: 'Value' };

    const readings = props.data.readings;
    const times = readings.map((r) => {
        if (r.timestamp) {
            return new Date(r.timestamp).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
            });
        }
        return '';
    });

    let valueKey = 'value';
    if (readings.length > 0) {
        const keys = Object.keys(readings[0]);
        if (keys.includes(props.type)) {
            valueKey = props.type;
        } else if (keys.includes('value')) {
            valueKey = 'value';
        } else if (keys.length > 1 && keys[1] !== 'timestamp') {
            valueKey = keys[1];
        }
    }

    const values = readings.map((r) => r[valueKey]);

    return { times, values, valueKey };
});

const lineChartOption = computed(() => {
    const { times, values, valueKey } = lineChartData.value;

    return {
        tooltip: {
            trigger: 'axis',
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true,
        },
        xAxis: {
            type: 'category',
            data: times,
            axisLabel: {
                rotate: 45,
            },
        },
        yAxis: {
            type: 'value',
            name: valueKey,
        },
        series: [
            {
                name: valueKey,
                type: 'line',
                data: values,
                smooth: true,
                markPoint: {
                    data: [
                        { type: 'max', name: 'Maximum' },
                        { type: 'min', name: 'Minimum' },
                    ],
                },
            },
        ],
    };
});

// NOWE - Bar chart dla sunlight
const barChartOption = computed(() => {
    if (props.type === 'sunlight') {
        return {
            tooltip: {
                trigger: 'axis',
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: ['Sunshine Hours', 'Solar Radiation', 'UV Index', 'Cloud Cover'],
            },
            yAxis: {
                type: 'value',
            },
            series: [
                {
                    name: 'Solar Data',
                    type: 'bar',
                    data: [
                        props.data.sunshine_hours || 0,
                        props.data.solar_radiation_mj_m2 || 0,
                        props.data.uv_index || 0,
                        props.data.cloud_cover_percent || 0
                    ],
                    itemStyle: {
                        color: function(params) {
                            const colors = ['#FFC107', '#FF9800', '#FF5722', '#9E9E9E'];
                            return colors[params.dataIndex];
                        }
                    }
                },
            ],
        };
    }

    return {};
});

// NOWE - Pie chart (można dodać dla growth_stage distribution)
const pieChartOption = computed(() => {
    return {
        tooltip: {
            trigger: 'item'
        },
        legend: {
            top: '5%',
            left: 'center'
        },
        series: [
            {
                name: 'Distribution',
                type: 'pie',
                radius: ['40%', '70%'],
                avoidLabelOverlap: false,
                itemStyle: {
                    borderRadius: 10,
                    borderColor: '#fff',
                    borderWidth: 2
                },
                label: {
                    show: false,
                    position: 'center'
                },
                emphasis: {
                    label: {
                        show: true,
                        fontSize: '40',
                        fontWeight: 'bold'
                    }
                },
                labelLine: {
                    show: false
                },
                data: []
            }
        ]
    };
});

// Heatmap pozostaje bez zmian
const heatmapData = computed(() => {
    if (!props.data || !Array.isArray(props.data.grid)) return { data: [] };

    const grid = props.data.grid;
    const data = [];

    for (let i = 0; i < grid.length; i++) {
        for (let j = 0; j < grid[i].length; j++) {
            data.push([j, i, grid[i][j]]);
        }
    }

    return { data };
});

const heatmapOption = computed(() => {
    const { data } = heatmapData.value;
    const maxValue = data.length > 0 ? Math.max(...data.map(item => item[2])) : 1;

    return {
        tooltip: {
            position: 'top',
        },
        grid: {
            height: '70%',
            top: '10%',
        },
        xAxis: {
            type: 'category',
            data: data.length > 0 ? Array.from({ length: Math.max(...data.map(item => item[0])) + 1 }, (_, i) => i + 1) : [],
            splitArea: {
                show: true,
            },
        },
        yAxis: {
            type: 'category',
            data: data.length > 0 ? Array.from({ length: Math.max(...data.map(item => item[1])) + 1 }, (_, i) => i + 1) : [],
            splitArea: {
                show: true,
            },
        },
        visualMap: {
            min: 0,
            max: maxValue,
            calculable: true,
            orient: 'horizontal',
            left: 'center',
            bottom: '15%',
        },
        series: [
            {
                name: 'Value',
                type: 'heatmap',
                data: data,
                label: {
                    show: false,
                },
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowColor: 'rgba(0, 0, 0, 0.5)',
                    },
                },
            },
        ],
    };
});
</script>

<style scoped>
.data-visualization {
    width: 100%;
}

.chart-container {
    width: 100%;
    height: 400px;
}

.chart {
    width: 100%;
    height: 100%;
}

.summary-cards .card {
    border: 1px solid #e5e9f2;
    transition: all 0.3s ease;
}

.summary-cards .card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.summary-cards .subtitle {
    color: #8094ae;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.5rem;
}

.summary-cards .amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #364a63;
    display: block;
    margin-bottom: 0.25rem;
}

.summary-cards .unit {
    color: #8094ae;
    font-size: 0.875rem;
}

.no-visualization pre {
    background-color: #f5f6fa;
    border-radius: 4px;
    padding: 1rem;
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    background-color: #cff4fc;
    border: 1px solid #9eeaf9;
    color: #055160;
}
</style>
