<template>
    <div class="row g-3">
        <div v-for="reading in readings" :key="reading.type" class="col-6 col-md-4 col-xl-3">
            <div class="card h-100 reading-tile">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="reading-label">{{ reading.label }}</span>
                        <em
                            v-if="reading.trend"
                            :class="trendIcon(reading.trend)"
                            class="icon ni reading-trend"
                            role="img"
                            :aria-label="`Trend: ${reading.trend}`"
                        ></em>
                    </div>
                    <div class="reading-value" :class="{ 'reading-value-text': reading.kind === 'text' }">
                        {{ reading.display }}
                        <span v-if="reading.unit" class="reading-unit">{{ reading.unit }}</span>
                    </div>
                    <div class="reading-date">{{ reading.date }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    readings: {
        type: Array,
        required: true,
    },
});

const trendIcon = (trend) => {
    if (trend === 'up') return 'ni-arrow-up text-success';
    if (trend === 'down') return 'ni-arrow-down text-danger';
    return 'ni-minus text-soft';
};
</script>

<style scoped>
.reading-label {
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #8094ae;
}

.reading-trend {
    font-size: 1.1rem;
}

.reading-value {
    margin-top: 0.5rem;
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.2;
    color: #364a63;
}

.reading-value-text {
    font-size: 1.1rem;
}

.reading-unit {
    font-size: 0.9rem;
    font-weight: 500;
    color: #8094ae;
}

.reading-date {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #8094ae;
}
</style>
