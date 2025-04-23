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

        <!-- Heatmap -->
        <div v-else-if="visualizationType === 'heatmap'" class="chart-container">
            <v-chart class="chart" :option="heatmapOption" autoresize />
        </div>

        <!-- No suitable visualization -->
        <div v-else class="no-visualization">
            <div class="alert alert-info">
                No suitable visualization available for this data type.
                <pre class="mt-3">{{ JSON.stringify(data, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart, HeatmapChart } from 'echarts/charts';
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
    console.log(props.type);
    switch (props.type) {
        case 'soil_moisture':
        case 'temperature':
            return hasTimeSeriesData.value ? 'line-chart' : 'table';
        case 'ndvi':
            return hasHeatmapData.value ? 'heatmap' : 'table';
        default:
            return 'table';
    }
});

const hasTimeSeriesData = computed(() => {
    if (!props.data || !Array.isArray(props.data.readings)) return false;
    return props.data.readings.length > 0 && props.data.readings[0].timestamp !== undefined;
});

const hasHeatmapData = computed(() => {
    if (!props.data || !Array.isArray(props.data.grid)) return false;
    return props.data.grid.length > 0;
});

const tableData = computed(() => {
    if (!props.data) return [];

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

// Prepare line chart data and options
const lineChartData = computed(() => {
    if (!props.data || !Array.isArray(props.data.readings)) return { times: [], values: [] };

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
            name: formatHeader(valueKey),
        },
        series: [
            {
                name: formatHeader(valueKey),
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

// Prepare heatmap data and options
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

.no-visualization pre {
    background-color: #f5f6fa;
    border-radius: 4px;
    padding: 1rem;
    overflow-x: auto;
    max-height: 400px;
    overflow-y: auto;
}
</style>
