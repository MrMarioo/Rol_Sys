<script setup>
import { computed } from 'vue';
import { FieldStatus } from '@/Enum';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const BADGE_CLASSES = {
    [FieldStatus.Active]: 'bg-success',
    [FieldStatus.Inactive]: 'bg-secondary',
    [FieldStatus.Maintenance]: 'bg-warning',
};

const badgeClass = computed(() => BADGE_CLASSES[props.status] ?? 'bg-secondary');

const label = computed(() => (props.status ? props.status.charAt(0).toUpperCase() + props.status.slice(1) : ''));
</script>

<template>
    <span class="badge" :class="badgeClass">{{ label }}</span>
</template>
