<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);

const lockScroll = (locked) => {
    document.body.classList.toggle('modal-open', locked);
    document.body.style.overflow = locked ? 'hidden' : null;
};

watch(() => props.show, (visible) => lockScroll(visible));

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    if (props.show) {
        lockScroll(true);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    if (props.show) {
        lockScroll(false);
    }
});

const sizeClass = computed(() => ({
    sm: 'modal-sm',
    md: '',
    lg: 'modal-lg',
    xl: 'modal-xl',
    '2xl': 'modal-xl',
})[props.maxWidth] ?? '');
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="show" class="modal-layer">
                <div class="modal-backdrop show" />
                <div
                    class="modal d-block"
                    tabindex="-1"
                    role="dialog"
                    aria-modal="true"
                    @click.self="close"
                >
                    <div class="modal-dialog modal-dialog-scrollable" :class="sizeClass" role="document">
                        <div class="modal-content">
                            <slot />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-layer {
    position: relative;
    z-index: 1055;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-active .modal-dialog,
.modal-fade-leave-active .modal-dialog {
    transition: transform 0.2s ease-out;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-from .modal-dialog,
.modal-fade-leave-to .modal-dialog {
    transform: translateY(-1.5rem);
}
</style>
