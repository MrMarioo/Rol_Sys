<script setup>
import SectionTitle from './SectionTitle.vue';
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
            <div class="card card-bordered">
                <div class="card-inner">
                    <slot name="content" />
                </div>
            </div>
        </div>
    </div>
</template>
