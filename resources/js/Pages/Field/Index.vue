<script>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FieldStatusBadge from '@/Components/FieldStatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchForm from './SearchForm.vue';
import { ConfirmModal } from '@/Helpers';
import { router } from '@inertiajs/vue3';
import CreateForm from '@/Pages/Field/CreateForm.vue';
import { ref } from 'vue';

export default {
    layout: AppLayout,
    components: {
        CreateForm,
        Pagination,
        Head,
        Link,
        FieldStatusBadge,
        SearchForm,
    },
    props: {
        fields: Object,
        statuses: Array,
        crops: Array,
    },
    setup() {
        const createFormRef = ref(null);

        const openCreateForm = () => {
            if (createFormRef.value) {
                createFormRef.value.openModal();
            }
        };

        return {
            createFormRef,
            openCreateForm,
        };
    },
    methods: {
        deleteField(fieldId) {
            ConfirmModal.fire({
                title: 'Delete Field',
                text: 'Are you sure you want to delete this field?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(route('fields.destroy', fieldId), {
                        preserveScroll: true,
                    });
                }
            });
        },
        refreshFields() {
            router.reload({ only: ['fields'] });
        },
    },
};
</script>

<template>
    <Head title="Fields Management" />

    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Fields Management</h3>
                <div class="nk-block-des text-soft">
                    <p>You have total {{ fields.total }} fields</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <div class="toggle-wrap nk-block-tools-toggle">
                    <button @click="openCreateForm" class="btn btn-primary">
                        <em class="icon ni ni-plus"></em>
                        <span>Add Field</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <CreateForm ref="createFormRef" :crops="crops" :statuses="statuses" @field-created="refreshFields" />

    <div class="nk-block">
        <div class="card card-stretch">
            <div class="card-inner-group">
                <div class="card-inner">
                    <!-- Search Form -->
                    <SearchForm :statuses="statuses" :crops="crops" />
                </div>

                <div v-if="fields.data.length === 0" class="card-inner p-5 text-center">
                    <div class="nk-data-empty">
                        <div class="nk-data-empty-icon">
                            <em class="icon ni ni-search"></em>
                        </div>
                        <h5 class="nk-data-empty-title">No fields found</h5>
                        <p class="nk-data-empty-note">Try changing your search criteria or add a new field.</p>
                        <button @click="openCreateForm" class="btn btn-primary">
                            <em class="icon ni ni-plus"></em>
                            <span>Add Field</span>
                        </button>
                    </div>
                </div>

                <div v-else class="card-inner p-0">
                    <div class="nk-tb-list">
                        <div class="nk-tb-item nk-tb-head">
                            <div class="nk-tb-col"><span>#</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Name</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Location</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Size (ha)</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Current Crop</span></div>
                            <div class="nk-tb-col tb-col-md"><span>Status</span></div>
                            <div class="nk-tb-col nk-tb-col-tools"></div>
                        </div>
                        <!-- .nk-tb-item -->
                        <div v-for="(field, index) in fields.data" :key="field.id" class="nk-tb-item">
                            <div class="nk-tb-col">
                                <span class="tb-lead">{{ fields.from + index }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">
                                    <Link :href="route('fields.show', field.id)" class="text-primary">{{ field.name }}</Link>
                                </span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">{{ field.location || 'Not specified' }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span class="tb-lead">{{ field.size }}</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <span v-if="field.crops && field.crops.length > 0" class="tb-lead">
                                    {{ field.crops[0].name }}
                                </span>
                                <span v-else class="tb-lead text-soft">No crops</span>
                            </div>
                            <div class="nk-tb-col tb-col-md">
                                <FieldStatusBadge :status="field.status" />
                            </div>
                            <div class="nk-tb-col nk-tb-col-tools">
                                <ul class="nk-tb-actions gx-1">
                                    <li>
                                        <div class="drodown">
                                            <a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-bs-toggle="dropdown">
                                                <em class="icon ni ni-more-h"></em>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <ul class="link-list-opt no-bdr">
                                                    <li>
                                                        <Link :href="route('fields.show', field.id)">
                                                            <em class="icon ni ni-eye"></em>
                                                            <span>View</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link :href="route('fields.edit', field.id)">
                                                            <em class="icon ni ni-edit"></em>
                                                            <span>Edit</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link :href="route('fields.data', field.id)">
                                                            <em class="icon ni ni-server"></em>
                                                            <span>Data</span>
                                                        </Link>
                                                    </li>
                                                    <li>
                                                        <Link :href="route('fields.analytics', field.id)">
                                                            <em class="icon ni ni-growth"></em>
                                                            <span>Analytics</span>
                                                        </Link>
                                                    </li>
                                                    <li class="divider"></li>
                                                    <li>
                                                        <a href="#" @click.prevent="deleteField(field.id)">
                                                            <em class="icon ni ni-trash"></em>
                                                            <span>Delete</span>
                                                        </a>
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

                <!-- Pagination -->
                <div class="card-inner pb-3 pt-3">
                    <div class="d-flex justify-content-center">
                        <Pagination :links="fields.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
