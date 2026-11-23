<template>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">Trend</h5>
            <select v-model="selectedType" class="form-select form-select-sm trend-select">
                <option v-for="(item, type) in series" :key="type" :value="type">{{ item.label }}</option>
            </select>
        </div>
        <div class="card-body">
            <v-chart class="trend-chart" :option="chartOption" autoresize />
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart } from 'echarts/charts';
import { GridComponent, TooltipComponent } from 'echarts/components';
import VChart from 'vue-echarts';

use([CanvasRenderer, LineChart, GridComponent, TooltipComponent]);

const props = defineProps({
    series: {
        type: Object,
        required: true,
    },
});

const defaultType = () => ('ndvi' in props.series ? 'ndvi' : Object.keys(props.series)[0]);

const selectedType = ref(defaultType());

watch(
    () => props.series,
    () => {
        if (!(selectedType.value in props.series)) {
            selectedType.value = defaultType();
        }
    },
);

const chartOption = computed(() => {
    const current = props.series[selectedType.value];
    const points = current?.points ?? [];

    return {
        grid: { left: 48, right: 16, top: 32, bottom: 32 },
        tooltip: {
            trigger: 'axis',
            valueFormatter: (value) => (current?.unit ? `${value} ${current.unit}` : `${value}`),
        },
        xAxis: {
            type: 'category',
            data: points.map((point) => point.date),
            boundaryGap: false,
        },
        yAxis: {
            type: 'value',
            name: current?.unit ?? '',
            scale: true,
        },
        series: [
            {
                name: current?.label ?? '',
                type: 'line',
                smooth: true,
                showSymbol: points.length <= 30,
                data: points.map((point) => point.value),
                itemStyle: { color: '#6576ff' },
                areaStyle: { opacity: 0.08 },
            },
        ],
    };
});
</script>

<style scoped>
.trend-chart {
    height: 320px;
    width: 100%;
}

.trend-select {
    width: auto;
    min-width: 180px;
}
</style>
