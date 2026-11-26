<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: 'no_data',
    },
});

const STATUSES = {
    excellent: { class: 'bg-success', label: 'Excellent' },
    good: { class: 'badge-dim bg-outline-success', label: 'Good' },
    fair: { class: 'bg-warning', label: 'Fair' },
    poor: { class: 'bg-danger', label: 'Poor' },
    no_data: { class: 'bg-secondary', label: 'No Data' },
};

const config = computed(() => STATUSES[props.status] ?? { class: 'bg-secondary', label: props.status });
</script>

<template>
    <span class="badge" :class="config.class">{{ config.label }}</span>
</template>

<style scoped>
.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}
</style>
