<template>
    <div class="nk-app-root">
        <Head :title="title" />
        <!-- main @s -->
        <div class="nk-main">
            <!-- wrap @s -->
            <div class="nk-wrap">
                <div class="nk-header nk-header-fixed is-light">
                    <div class="container-lg wide-xl">
                        <div class="nk-header-wrap">
                            <div class="nk-header-brand">
                                <a href="/public" class="logo-link">
                                    <AuthenticationCardLogo styles="size-16" color="gray" />
                                </a>
                            </div>
                            <!-- .nk-header-brand -->
                            <div class="nk-header-tools">
                                <ul class="nk-quick-nav">
                                    <li class="dropdown user-dropdown">
                                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                                            <div class="user-toggle">
                                                <div class="user-avatar sm">
                                                    <em class="icon ni ni-user-alt"></em>
                                                </div>
                                                <div class="user-name dropdown-indicator d-none d-sm-block">{{ user?.name }}</div>
                                            </div>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end dropdown-menu-s1">
                                            <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                                <div class="user-card">
                                                    <div class="user-avatar">
                                                        <span>AB</span>
                                                    </div>
                                                    <div class="user-info">
                                                        <span class="lead-text">{{ user?.name }}</span>
                                                        <span class="sub-text">{{ user?.email }}</span>
                                                    </div>
                                                    <div class="user-action">
                                                        <a class="btn btn-icon me-n2" href="#"><em class="icon ni ni-setting"></em></a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <!--                                                    <li>-->
                                                    <!--                                                        <Link href="/user/profile">-->
                                                    <!--                                                            <em class="icon ni ni-user-alt"></em>-->
                                                    <!--                                                            <span>View Profile</span>-->
                                                    <!--                                                        </Link>-->
                                                    <!--                                                    </li>-->
                                                    <!--                                                    <li>-->
                                                    <!--                                                        <Link href="/dashboard/login-activity">-->
                                                    <!--                                                            <em class="icon ni ni-activity-alt"></em>-->
                                                    <!--                                                            <span>Login Activity</span>-->
                                                    <!--                                                        </Link>-->
                                                    <!--                                                    </li>-->
                                                    <!-- <li><Link href="/dashboard/subscription"><em class="icon ni ni-sign-gbp"></em><span>Pricing</span></Link></li> -->
                                                    <li>
                                                        <a class="dark-switch" :class="{ active: theme == 'dark' }" @click="toggleTheme" href="#">
                                                            <em class="icon ni ni-moon"></em>
                                                            <span>Dark Mode</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="dropdown-inner">
                                                <ul class="link-list">
                                                    <li>
                                                        <a href="#" @click="logOut">
                                                            <em class="icon ni ni-signout"></em>
                                                            <span>Sign out</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- .dropdown -->
                                    <li class="dropdown notification-dropdown">
                                        <a href="#" class="dropdown-toggle nk-quick-nav-icon me-lg-n1" data-bs-toggle="dropdown">
                                            <div class="icon-status icon-status-info"><em class="icon ni ni-bell"></em></div>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-xl dropdown-menu-end dropdown-menu-s1">
                                            <div class="dropdown-head">
                                                <span class="sub-title nk-dropdown-title">Notifications</span>
                                                <a href="#">Mark All as Read</a>
                                            </div>
                                            <div class="dropdown-body">
                                                <div class="nk-notification">
                                                    <div class="nk-notification-item dropdown-inner">
                                                        <div class="nk-notification-icon">
                                                            <em class="icon icon-circle bg-warning-dim ni ni-curve-down-right"></em>
                                                        </div>
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text">
                                                                You have requested to
                                                                <span>Widthdrawal</span>
                                                            </div>
                                                            <div class="nk-notification-time">2 hrs ago</div>
                                                        </div>
                                                    </div>
                                                    <div class="nk-notification-item dropdown-inner">
                                                        <div class="nk-notification-icon">
                                                            <em class="icon icon-circle bg-success-dim ni ni-curve-down-left"></em>
                                                        </div>
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text">
                                                                Your
                                                                <span>Deposit Order</span>
                                                                is placed
                                                            </div>
                                                            <div class="nk-notification-time">2 hrs ago</div>
                                                        </div>
                                                    </div>
                                                    <div class="nk-notification-item dropdown-inner">
                                                        <div class="nk-notification-icon">
                                                            <em class="icon icon-circle bg-warning-dim ni ni-curve-down-right"></em>
                                                        </div>
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text">
                                                                You have requested to
                                                                <span>Widthdrawal</span>
                                                            </div>
                                                            <div class="nk-notification-time">2 hrs ago</div>
                                                        </div>
                                                    </div>
                                                    <div class="nk-notification-item dropdown-inner">
                                                        <div class="nk-notification-icon">
                                                            <em class="icon icon-circle bg-success-dim ni ni-curve-down-left"></em>
                                                        </div>
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text">
                                                                Your
                                                                <span>Deposit Order</span>
                                                                is placed
                                                            </div>
                                                            <div class="nk-notification-time">2 hrs ago</div>
                                                        </div>
                                                    </div>
                                                    <div class="nk-notification-item dropdown-inner">
                                                        <div class="nk-notification-icon">
                                                            <em class="icon icon-circle bg-warning-dim ni ni-curve-down-right"></em>
                                                        </div>
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text">
                                                                You have requested to
                                                                <span>Widthdrawal</span>
                                                            </div>
                                                            <div class="nk-notification-time">2 hrs ago</div>
                                                        </div>
                                                    </div>
                                                    <div class="nk-notification-item dropdown-inner">
                                                        <div class="nk-notification-icon">
                                                            <em class="icon icon-circle bg-success-dim ni ni-curve-down-left"></em>
                                                        </div>
                                                        <div class="nk-notification-content">
                                                            <div class="nk-notification-text">
                                                                Your
                                                                <span>Deposit Order</span>
                                                                is placed
                                                            </div>
                                                            <div class="nk-notification-time">2 hrs ago</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- .nk-notification -->
                                            </div>
                                            <!-- .nk-dropdown-body -->
                                            <div class="dropdown-foot justify-center">
                                                <a href="#">View All</a>
                                            </div>
                                        </div>
                                    </li>
                                    <!-- .dropdown -->
                                    <li class="d-lg-none dropdown">
                                        <a href="#" class="dropdown-toggle nk-quick-nav-icon p-2" data-bs-toggle="dropdown">
                                            <em class="icon ni ni-menu"></em>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg">
                                            <div class="dropdown-inner">
                                                <ul class="link-list-plain">
                                                    <li>
                                                        <Link href="/" class="dropdown-item d-flex align-items-center">
                                                            <em class="icon ni ni-dashboard me-2"></em>
                                                            <span>Dashboard</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link href="/fields" class="dropdown-item d-flex align-items-center">
                                                            <em class="icon ni map me-2"></em>
                                                            <span>Fields</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link href="/field-data" class="dropdown-item d-flex align-items-center">
                                                            <em class="icon ni ni-server me-2"></em>
                                                            <span>Fields Data</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link href="/analytics" class="dropdown-item d-flex align-items-center">
                                                            <em class="icon ni growth me-2"></em>
                                                            <span>Analytics</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link href="/reports" class="dropdown-item d-flex align-items-center">
                                                            <em class="icon ni files me-2"></em>
                                                            <span>Reports</span>
                                                        </Link>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                                <!-- .nk-quick-nav -->
                            </div>
                            <!-- .nk-header-tools -->
                        </div>
                        <!-- .nk-header-wrap -->
                    </div>
                    <!-- .container-fliud -->
                </div>
                <!-- main header @e -->
                <!-- content @s -->
                <div class="nk-content">
                    <div class="wide-xl container">
                        <div class="nk-content-inner">
                            <div class="nk-aside" data-content="sideNav" data-toggle-overlay="true" data-toggle-screen="lg" data-toggle-body="true">
                                <div class="nk-sidebar-menu" data-simplebar>
                                    <!-- Menu -->
                                    <ul class="nk-menu">
                                        <li class="nk-menu-heading">
                                            <h6 class="overline-title">Menu</h6>
                                        </li>
                                        <li class="nk-menu-item">
                                            <Link href="/" class="nk-menu-link">
                                                <span class="nk-menu-icon"><em class="icon ni ni-dashboard"></em></span>
                                                <span class="nk-menu-text">Dashboard</span>
                                            </Link>
                                        </li>
                                        <li class="nk-menu-item">
                                            <Link href="/fields" class="nk-menu-link">
                                                <span class="nk-menu-icon"><em class="icon ni ni-map"></em></span>
                                                <span class="nk-menu-text">Fields</span>
                                            </Link>
                                        </li>
                                        <li class="nk-menu-item">
                                            <Link href="/field-data" class="nk-menu-link">
                                                <span class="nk-menu-icon"><em class="icon ni ni-server"></em></span>
                                                <span class="nk-menu-text">Fields Data</span>
                                            </Link>
                                        </li>
                                        <li class="nk-menu-item">
                                            <Link href="/analytics" class="nk-menu-link">
                                                <span class="nk-menu-icon"><em class="icon ni ni-growth"></em></span>
                                                <span class="nk-menu-text">Analitycs</span>
                                            </Link>
                                        </li>
                                        <li class="nk-menu-item">
                                            <Link href="/reports" class="nk-menu-link">
                                                <span class="nk-menu-icon"><em class="icon ni ni-files"></em></span>
                                                <span class="nk-menu-text">Reports</span>
                                            </Link>
                                        </li>
                                        <!-- <li class="nk-menu-item">
                                            <a href="html/subscription/profile-setting.html" class="nk-menu-link">
                                                <span class="nk-menu-icon"><em class="icon ni ni-account-setting"></em></span>
                                                <span class="nk-menu-text">Account Setting</span>
                                            </a>
                                        </li> -->
                                    </ul>
                                    <!-- Menu -->
                                    <ul class="nk-menu nk-menu-sm" style="display: none">
                                        <!-- Menu -->
                                        <li class="nk-menu-heading">
                                            <span>Help Center</span>
                                        </li>
                                        <li class="nk-menu-item">
                                            <a href="html/subscription/faqs.html" class="nk-menu-link">
                                                <span class="nk-menu-text">FAQs</span>
                                            </a>
                                        </li>
                                        <li class="nk-menu-item">
                                            <a href="html/subscription/contact.html" class="nk-menu-link">
                                                <span class="nk-menu-text">Contact</span>
                                            </a>
                                        </li>
                                        <li class="nk-menu-item">
                                            <a href="html/subscription/support.html" class="nk-menu-link">
                                                <span class="nk-menu-text">Support</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <!-- .nk-sidebar-menu -->
                                <div class="nk-aside-close">
                                    <a href="#" class="toggle" data-target="sideNav"><em class="icon ni ni-cross"></em></a>
                                </div>
                                <!-- .nk-aside-close -->
                            </div>
                            <!-- .nk-aside -->
                            <div class="nk-content-body">
                                <div class="nk-content-wrap">
                                    <!-- <div class="nk-block-head nk-block-head-lg">
                                        <div class="nk-block-between-md g-4">
                                            <div class="nk-block-head-content">
                                                <h2 class="nk-block-title fw-normal">Welcome, {{ user.name }}</h2>
                                                    <div class="nk-block-des">
                                                    </div>
                                            </div>
                                        </div>
                                    </div> -->
                                    <!-- .nk-block-head -->

                                    <slot></slot>
                                </div>
                                <!-- footer @s -->
                                <div class="nk-footer">
                                    <div class="wide-xl container">
                                        <div class="nk-footer-wrap g-2">
                                            <div class="nk-footer-copyright">
                                                Copyright &copy; 2024-{{ new Date().getFullYear() }} Mariusz Śmistek.
                                            </div>
                                            <div class="nk-footer-links">
                                                <ul class="nav nav-sm"></ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- footer @e -->
                            </div>
                        </div>
                    </div>
                </div>
                <!-- content @e -->
            </div>
            <!-- wrap @e -->
        </div>
        <!-- main @e -->
    </div>
    <!-- app-root @e -->
</template>

<script>
import { Head, Link, usePage } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';
import axios from 'axios';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import { Dropdown } from 'bootstrap';

export default {
    props: {
        title: String,
        breadcrumbs: Array,
        user: Object,
    },

    components: {
        AuthenticationCardLogo,
        Head,
        Link,
    },

    setup(props) {
        document.querySelector('body').classList.add('nk-body');
        document.querySelector('body').classList.add('bg-white');
        document.querySelector('body').classList.add('npc-subscription');
        document.querySelector('body').classList.add('has-aside');
        document.querySelector('body').classList.add('nk-nio-theme');
        document.querySelector('html').classList.remove('noauth');
        document.querySelector('html').classList.add('authenticated');

        const theme = ref(document.querySelector('body').classList.contains('dark-mode') ? 'dark' : 'light');
        const toggleTheme = () => {
            document.querySelector('body').classList.contains('dark-mode')
                ? document.querySelector('body').classList.remove('dark-mode')
                : document.querySelector('body').classList.add('dark-mode');
            theme.value = document.querySelector('body').classList.contains('dark-mode') ? 'dark' : 'light';
        };

        const logOut = () => {
            axios.post(route('logout'), {}).then((response) => {
                window.location.href = '/';
            });
        };

        // document.documentElement.classList.add('h-auth')

        const presentComingSoon = () => {
            alert('This feature will be coming back soon! In the meantime, check out our address lists.');
            return false;
        };

        const toggleMobileMenu = () => {
            const mobilMenu = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            if (mobilMenu) {
                mobilMenu.classList.toggle('mobile-menu-active');
            }
        };

        return {
            theme,
            logOut,
            toggleTheme,
            presentComingSoon,
            toggleMobileMenu,
        };
    },
    mounted() {
        const dropdownElementList = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        dropdownElementList.forEach((item) => {
            new Dropdown(item);
        });
        // const flash = this.$page.props.flash;
        //
        // if (flash.success) {
        //     Swal.fire({
        //         icon: 'success',
        //         title: 'Success',
        //         text: flash.success,
        //         toast: true,
        //         showConfirmButton: false,
        //         position: 'top-end',
        //         timer: 3000,
        //     });
        // }
        //
        // if (flash.error) {
        //     Swal.fire({
        //         icon: 'error',
        //         title: 'Error',
        //         text: flash.error,
        //         toast: true,
        //         showConfirmButton: false,
        //         position: 'top-end',
        //         timer: 3000,
        //     });
        // }
    },
};
</script>

<style scoped>
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
