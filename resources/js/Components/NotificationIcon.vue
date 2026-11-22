<template>
    <em :class="['icon', 'icon-circle', style.bg, 'ni', style.icon]"></em>
</template>

<script>
const STYLES = {
    success: { bg: 'bg-success-dim', icon: 'ni-check-circle' },
    danger: { bg: 'bg-danger-dim', icon: 'ni-alert-circle' },
    warning: { bg: 'bg-warning-dim', icon: 'ni-alert' },
    info: { bg: 'bg-info-dim', icon: 'ni-info' },
};

export default {
    props: {
        level: { type: String, default: 'info' },
    },
    computed: {
        style() {
            return STYLES[this.level] || STYLES.info;
        },
    },
};
</script>
