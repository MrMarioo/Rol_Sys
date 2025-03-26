<template>
    <Link :href="'/'" class="d-flex justify-content-center mb-4">
        <img src="/assets/img/ap-logo-02.png" />
    </Link>
</template>

<script>
import { defineComponent } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';

export default defineComponent({
    components: {
        Link,
    },
});
</script>

<style scoped>
img {
    width: 60%;
    margin-bottom: 20px;
}
</style>
