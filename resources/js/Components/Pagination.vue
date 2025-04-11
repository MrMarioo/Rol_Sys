<script>
import { Link } from '@inertiajs/vue3';

export default {
    components: {
        Link,
    },
    props: {
        links: {
            type: Array,
            required: true,
        },
    },
};
</script>

<template>
    <div v-if="links.length > 3" class="d-flex justify-content-center">
        <nav>
            <ul class="pagination pagination-sm">
                <!-- Previous Page Link -->
                <li class="page-item" :class="{ disabled: !links[0].url }">
                    <Link v-if="links[0].url" class="page-link" :href="links[0].url" preserve-scroll>
                        <em class="icon ni ni-chevron-left"></em>
                    </Link>
                    <span v-else class="page-link">
                        <em class="icon ni ni-chevron-left"></em>
                    </span>
                </li>

                <!-- Numbered Page Links -->
                <li v-for="(link, key) in links.slice(1, -1)" :key="key" class="page-item" :class="{ active: link.active }">
                    <Link v-if="link.url" class="page-link" :href="link.url" preserve-scroll v-html="link.label"></Link>
                    <span v-else class="page-link" v-html="link.label"></span>
                </li>

                <!-- Next Page Link -->
                <li class="page-item" :class="{ disabled: !links[links.length - 1].url }">
                    <Link v-if="links[links.length - 1].url" class="page-link" :href="links[links.length - 1].url" preserve-scroll>
                        <em class="icon ni ni-chevron-right"></em>
                    </Link>
                    <span v-else class="page-link">
                        <em class="icon ni ni-chevron-right"></em>
                    </span>
                </li>
            </ul>
        </nav>
    </div>
</template>
