<template>
    <div>
        <Head title="Notifications" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Notifications</h3>
                    <div class="nk-block-des text-soft">
                        <p>You have total {{ history.total }} notifications</p>
                    </div>
                </div>
                <div class="nk-block-head-content">
                    <button type="button" class="btn btn-primary" @click="markAllAsRead">
                        <em class="icon ni ni-check-thick"></em>
                        <span>Mark All as Read</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <ul class="nav nav-tabs mb-3">
                <li v-for="tab in TABS" :key="tab.value" class="nav-item">
                    <Link
                        :href="route('notifications.index', tab.value ? { filter: { status: tab.value } } : {})"
                        :class="['nav-link', { active: currentStatus === tab.value }]"
                        preserve-scroll
                    >
                        {{ tab.label }}
                    </Link>
                </li>
            </ul>

            <div class="card card-stretch">
                <div class="card-inner-group">
                    <div v-if="history.data.length === 0" class="card-inner p-5 text-center text-soft">
                        No notifications.
                    </div>
                    <div v-else class="card-inner p-0">
                        <div class="nk-notification">
                            <button
                                v-for="notification in history.data"
                                :key="notification.id"
                                type="button"
                                class="nk-notification-item dropdown-inner notification-row"
                                :class="{ 'is-unread': !notification.read }"
                                @click="open(notification)"
                            >
                                <div class="nk-notification-icon">
                                    <NotificationIcon :level="notification.level" />
                                </div>
                                <div class="nk-notification-content">
                                    <div class="nk-notification-text">
                                        <span>{{ notification.title }}</span>
                                        <div>{{ notification.message }}</div>
                                    </div>
                                    <div class="nk-notification-time">{{ notification.created_at }}</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="card-inner pb-3 pt-3">
                        <Pagination :links="history.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import NotificationIcon from '@/Components/NotificationIcon.vue';

defineOptions({ layout: AppLayout });

defineProps({
    history: Object,
});

const TABS = [
    { label: 'All', value: null },
    { label: 'Unread', value: 'unread' },
    { label: 'Read', value: 'read' },
];

const page = usePage();

const currentStatus = computed(() => new URL(page.url, window.location.origin).searchParams.get('filter[status]'));

const open = (notification) => {
    router.post(route('notifications.read', notification.id));
};

const markAllAsRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};
</script>

<style scoped>
.notification-row {
    width: 100%;
    border: 0 solid #e5e9f2;
    background: transparent;
    font: inherit;
    text-align: left;
    color: inherit;
    cursor: pointer;
}
.notification-row + .notification-row {
    border-top-width: 1px;
}
.notification-row.is-unread .nk-notification-text span {
    font-weight: 700;
}
</style>
