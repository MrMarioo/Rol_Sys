<template>
    <div class="nk-app-root has-aside">
        <Head :title="title" />
        <div class="nk-main">
            <div class="nk-wrap">
                <div class="nk-header nk-header-fixed is-light">
                    <div class="container-lg wide-xl">
                        <div class="nk-header-wrap">
                            <div class="nk-header-brand">
                                <AuthenticationCardLogo class="logo-link" styles="app-logo" color="gray" />
                            </div>
                            <div class="nk-header-tools">
                                <ul class="nk-quick-nav">
                                    <li class="dropdown user-dropdown">
                                        <button type="button" class="dropdown-toggle btn-reset" data-bs-toggle="dropdown" aria-label="Account menu">
                                            <div class="user-toggle">
                                                <div class="user-avatar sm">
                                                    <em class="icon ni ni-user-alt"></em>
                                                </div>
                                                <div class="user-name dropdown-indicator d-none d-sm-block">{{ currentUser?.name }}</div>
                                            </div>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end dropdown-menu-s1">
                                            <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                                <div class="user-card">
                                                    <div class="user-avatar">
                                                        <span>{{ initials }}</span>
                                                    </div>
                                                    <div class="user-info">
                                                        <span class="lead-text">{{ currentUser?.name }}</span>
                                                        <span class="sub-text">{{ currentUser?.email }}</span>
                                                    </div>
                                                    <div class="user-action">
                                                        <Link class="btn btn-icon me-n2" :href="route('profile.show')" aria-label="Profile settings">
                                                            <em class="icon ni ni-setting"></em>
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li>
                                                        <Link :href="route('profile.show')">
                                                            <em class="icon ni ni-user-alt"></em>
                                                            <span>Profile</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dark-switch btn-reset" :class="{ active: isDark }" @click="toggleTheme">
                                                            <em class="icon ni ni-moon"></em>
                                                            <span>Dark Mode</span>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li>
                                                        <button type="button" class="btn-reset" @click="logOut">
                                                            <em class="icon ni ni-signout"></em>
                                                            <span>Sign out</span>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="dropdown notification-dropdown">
                                        <button
                                            type="button"
                                            class="dropdown-toggle nk-quick-nav-icon me-lg-n1 position-relative btn-reset"
                                            data-bs-toggle="dropdown"
                                            :aria-label="unreadCount > 0 ? `Notifications, ${unreadCount} unread` : 'Notifications'"
                                        >
                                            <div :class="['icon-status', unreadCount > 0 ? 'icon-status-danger' : 'icon-status-info']">
                                                <em class="icon ni ni-bell"></em>
                                            </div>
                                            <span v-if="unreadCount > 0" class="badge rounded-pill bg-danger notification-badge">
                                                {{ unreadCount > 99 ? '99+' : unreadCount }}
                                            </span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-xl dropdown-menu-end dropdown-menu-s1">
                                            <div class="dropdown-head">
                                                <span class="sub-title nk-dropdown-title">Notifications</span>
                                                <button v-if="unreadCount > 0" type="button" class="btn-reset link-primary" @click="markAllNotificationsAsRead">
                                                    Mark All as Read
                                                </button>
                                            </div>
                                            <div class="dropdown-body">
                                                <div class="nk-notification">
                                                    <div v-if="latestNotifications.length === 0" class="nk-notification-item dropdown-inner text-soft">
                                                        No notifications
                                                    </div>
                                                    <button
                                                        v-for="notification in latestNotifications"
                                                        :key="notification.id"
                                                        type="button"
                                                        class="nk-notification-item dropdown-inner notification-row btn-reset"
                                                        :class="{ 'is-unread': !notification.read }"
                                                        @click="openNotification(notification)"
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
                                            <div class="dropdown-foot justify-center">
                                                <Link :href="route('notifications.index')">View All</Link>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="d-lg-none dropdown">
                                        <button type="button" class="dropdown-toggle nk-quick-nav-icon p-2 btn-reset" data-bs-toggle="dropdown" aria-label="Main menu">
                                            <em class="icon ni ni-menu"></em>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg">
                                            <div class="dropdown-inner">
                                                <ul class="link-list-plain">
                                                    <li v-for="item in menu" :key="item.route">
                                                        <Link
                                                            :href="route(item.route)"
                                                            class="dropdown-item d-flex align-items-center"
                                                            :class="{ active: isActive(item) }"
                                                        >
                                                            <em class="icon ni me-2" :class="item.icon"></em>
                                                            <span>{{ item.label }}</span>
                                                        </Link>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nk-content">
                    <div class="wide-xl container">
                        <div class="nk-content-inner">
                            <nav class="nk-aside" aria-label="Main">
                                <div class="nk-sidebar-menu">
                                    <ul class="nk-menu">
                                        <li class="nk-menu-heading">
                                            <h6 class="overline-title">Menu</h6>
                                        </li>
                                        <li v-for="item in menu" :key="item.route" class="nk-menu-item" :class="{ active: isActive(item) }">
                                            <Link :href="route(item.route)" class="nk-menu-link" :aria-current="isActive(item) ? 'page' : undefined">
                                                <span class="nk-menu-icon"><em class="icon ni" :class="item.icon"></em></span>
                                                <span class="nk-menu-text">{{ item.label }}</span>
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </nav>
                            <div class="nk-content-body">
                                <div class="nk-content-wrap">
                                    <slot></slot>
                                </div>
                                <div class="nk-footer">
                                    <div class="wide-xl container">
                                        <div class="nk-footer-wrap g-2">
                                            <div class="nk-footer-copyright">
                                                Copyright &copy; 2024-{{ currentYear }} Mariusz Śmistek.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import NotificationIcon from '@/Components/NotificationIcon.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';

const props = defineProps({
    title: String,
    user: Object,
});

const menu = [
    { route: 'dashboard', pattern: 'dashboard', label: 'Dashboard', icon: 'ni-dashboard' },
    { route: 'fields.index', pattern: 'fields.*', label: 'Fields', icon: 'ni-map' },
    { route: 'field-data.index', pattern: 'field-data.*', label: 'Fields Data', icon: 'ni-server' },
    { route: 'analytics.index', pattern: 'analytics.*', label: 'Analytics', icon: 'ni-growth' },
    { route: 'reports.index', pattern: 'reports.*', label: 'Reports', icon: 'ni-files' },
];

const page = usePage();

const currentUser = computed(() => props.user ?? page.props.auth?.user);

const initials = computed(() =>
    (currentUser.value?.name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join(''),
);

const currentYear = new Date().getFullYear();

const isActive = (item) => route().current(item.pattern);

const unreadCount = computed(() => page.props.notifications?.unread_count ?? 0);
const latestNotifications = computed(() => page.props.notifications?.latest ?? []);

const isDark = ref(document.body.classList.contains('dark-mode'));

const toggleTheme = () => {
    isDark.value = document.body.classList.toggle('dark-mode');
};

const logOut = () => {
    router.post(route('logout'));
};

const openNotification = (notification) => {
    router.post(route('notifications.read', notification.id));
};

const markAllNotificationsAsRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};

let notificationPoll = null;

onMounted(() => {
    notificationPoll = setInterval(() => {
        if (!document.hidden) {
            router.reload({ only: ['notifications'] });
        }
    }, 60000);
});

onBeforeUnmount(() => clearInterval(notificationPoll));
</script>

<style scoped>
.notification-badge {
    position: absolute;
    top: -4px;
    right: -8px;
    font-size: 0.65rem;
    padding: 0.2em 0.45em;
}

.notification-dropdown .dropdown-menu {
    min-width: 360px;
}

.notification-row {
    width: 100%;
    text-align: left;
}

.link-list .btn-reset {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 0;
    color: #526484;
    font-size: 13px;
    font-weight: 500;
}

.link-list .btn-reset:hover {
    color: #6576ff;
}

.link-list .btn-reset .icon {
    font-size: 1.125rem;
    width: 1.75rem;
    opacity: 0.8;
}

.app-logo {
    width: 4rem;
    height: 4rem;
}

.notification-row.is-unread .nk-notification-text span {
    font-weight: 700;
}

.dropdown-menu {
    padding: 0.5rem 0;
    min-width: 240px;
    max-height: 75vh;
    overflow-y: auto;
}

.dropdown-item {
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    color: #526484;
}

.dropdown-item:hover {
    background-color: #f5f6fa;
    color: #364a63;
}

@media (max-width: 991px) {
    .dropdown-item {
        padding: 0.875rem 1.25rem;
        font-size: 1rem;
    }
}
</style>
