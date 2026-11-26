<template>
    <em :class="['icon', 'icon-circle', style.bg, 'ni', style.icon]"></em>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: {
        type: String,
        default: 'info',
    },
});

const STYLES = {
    success: { bg: 'bg-success-dim', icon: 'ni-check-circle' },
    danger: { bg: 'bg-danger-dim', icon: 'ni-alert-circle' },
    warning: { bg: 'bg-warning-dim', icon: 'ni-alert' },
    info: { bg: 'bg-info-dim', icon: 'ni-info' },
};

const style = computed(() => STYLES[props.level] ?? STYLES.info);
</script>
