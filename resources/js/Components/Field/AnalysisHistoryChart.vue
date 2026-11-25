<template>
    <div v-if="history.length >= 2" class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Analysis history</h5>
        </div>
        <div class="card-body">
            <v-chart class="history-chart" :option="chartOption" autoresize />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart } from 'echarts/charts';
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components';
import VChart from 'vue-echarts';

use([CanvasRenderer, LineChart, GridComponent, TooltipComponent, LegendComponent]);

const props = defineProps({
    history: {
        type: Array,
        default: () => [],
    },
});

const chartOption = computed(() => {
    const rows = [...props.history].reverse();

    return {
        grid: { left: 48, right: 48, top: 48, bottom: 32 },
        legend: { top: 0 },
        tooltip: { trigger: 'axis' },
        xAxis: {
            type: 'category',
            data: rows.map((row) => row.date),
            boundaryGap: false,
        },
        yAxis: [
            { type: 'value', name: 'NDVI', min: 0, max: 1 },
            { type: 'value', name: '%', min: 0, max: 100, splitLine: { show: false } },
        ],
        series: [
            {
                name: 'NDVI',
                type: 'line',
                yAxisIndex: 0,
                smooth: true,
                connectNulls: true,
                data: rows.map((row) => row.ndvi),
                itemStyle: { color: '#6576ff' },
            },
            {
                name: 'Moisture %',
                type: 'line',
                yAxisIndex: 1,
                smooth: true,
                connectNulls: true,
                data: rows.map((row) => row.moisture_percent),
                itemStyle: { color: '#09c2de' },
            },
            {
                name: 'Health score',
                type: 'line',
                yAxisIndex: 1,
                smooth: true,
                connectNulls: true,
                data: rows.map((row) => row.health_score),
                itemStyle: { color: '#1ee0ac' },
            },
        ],
    };
});
</script>

<style scoped>
.history-chart {
    height: 320px;
    width: 100%;
}
</style>
