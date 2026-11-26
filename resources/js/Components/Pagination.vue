<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="d-flex justify-content-center">
        <nav aria-label="Pagination">
            <ul class="pagination pagination-sm">
                <li class="page-item" :class="{ disabled: !links[0].url }">
                    <Link
                        v-if="links[0].url"
                        class="page-link"
                        :href="links[0].url"
                        preserve-scroll
                        aria-label="Previous page"
                    >
                        <em class="icon ni ni-chevron-left" aria-hidden="true"></em>
                    </Link>
                    <span v-else class="page-link">
                        <em class="icon ni ni-chevron-left" aria-hidden="true"></em>
                    </span>
                </li>

                <li v-for="(link, key) in links.slice(1, -1)" :key="key" class="page-item" :class="{ active: link.active }">
                    <Link
                        v-if="link.url"
                        class="page-link"
                        :href="link.url"
                        :aria-current="link.active ? 'page' : undefined"
                        preserve-scroll
                        v-html="link.label"
                    ></Link>
                    <span v-else class="page-link" v-html="link.label"></span>
                </li>

                <li class="page-item" :class="{ disabled: !links[links.length - 1].url }">
                    <Link
                        v-if="links[links.length - 1].url"
                        class="page-link"
                        :href="links[links.length - 1].url"
                        preserve-scroll
                        aria-label="Next page"
                    >
                        <em class="icon ni ni-chevron-right" aria-hidden="true"></em>
                    </Link>
                    <span v-else class="page-link">
                        <em class="icon ni ni-chevron-right" aria-hidden="true"></em>
                    </span>
                </li>
            </ul>
        </nav>
    </div>
</template>
