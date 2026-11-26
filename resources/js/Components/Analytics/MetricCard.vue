<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: '—',
    },
    unit: {
        type: String,
        default: '',
    },
    valueClass: {
        type: [String, Array, Object],
        default: '',
    },
    centered: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="card card-bordered">
        <div class="card-inner" :class="{ 'text-center': centered }">
            <div class="card-title-group align-start mb-2">
                <div class="card-title">
                    <h6 class="subtitle">{{ label }}</h6>
                </div>
            </div>
            <div class="nk-sale-data">
                <span class="amount" :class="valueClass">{{ value }}</span>
                <span v-if="unit" class="subtitle">{{ unit }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card-bordered {
    border: 1px solid #e5e9f2;
}

.subtitle {
    color: #526484;
    font-size: 12px;
}

.amount {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: #364a63;
}
</style>
