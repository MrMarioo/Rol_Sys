<script setup>
defineProps({
    on: Boolean,
});
</script>

<template>
    <div>
        <Transition name="action-message">
            <div v-show="on" class="small text-muted">
                <slot />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.action-message-leave-active {
    transition: opacity 1s ease-in;
}

.action-message-leave-to {
    opacity: 0;
}
</style>
