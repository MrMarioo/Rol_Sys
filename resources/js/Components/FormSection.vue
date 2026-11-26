<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const slots = useSlots();
const hasActions = computed(() => !! slots.actions);
</script>

<template>
    <div class="row g-gs">
        <div class="col-lg-4">
            <SectionTitle>
                <template #title>
                    <slot name="title" />
                </template>
                <template #description>
                    <slot name="description" />
                </template>
            </SectionTitle>
        </div>

        <div class="col-lg-8">
            <form class="card card-bordered" @submit.prevent="$emit('submitted')">
                <div class="card-inner">
                    <div class="row g-3">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="card-inner border-top bg-lighter d-flex align-items-center justify-content-end gap-2 py-3">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
