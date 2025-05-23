<template>
    <div class="modal fade" :class="{ 'show d-block': showModal }" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Field</h5>
                    <a href="#" class="close" @click.prevent="closeModal">
                        <em class="icon ni ni-cross"></em>
                    </a>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveField">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Draw Field on Map</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <FieldMap
                                            mode="create"
                                            height="500px"
                                            v-model:boundaries="form.boundaries"
                                            @area-calculated="updateFieldSize"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-name">
                                        Field Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="field-name"
                                            placeholder="Enter field name"
                                            v-model="form.name"
                                            required
                                        />
                                    </div>
                                    <div v-if="form.errors.name" class="form-note text-danger">{{ form.errors.name }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-location">Location</label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="field-location"
                                            placeholder="Enter location (optional)"
                                            v-model="form.location"
                                        />
                                    </div>
                                    <div v-if="form.errors.location" class="form-note text-danger">{{ form.errors.location }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-size">
                                        Size (hectares)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="number"
                                            class="form-control"
                                            id="field-size"
                                            placeholder="Enter field size"
                                            v-model="form.size"
                                            step="0.01"
                                            min="0.01"
                                            required
                                        />
                                    </div>
                                    <div v-if="form.errors.size" class="form-note text-danger">{{ form.errors.size }}</div>
                                    <div class="form-note">Size is calculated automatically based on the drawn area.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="field-status">
                                        Status
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <select class="form-select" id="field-status" v-model="form.status" required>
                                            <option v-for="status in statuses" :key="status" :value="status">
                                                {{ status === 'active' ? 'Active' : status === 'inactive' ? 'Inactive' : status }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.errors.status" class="form-note text-danger">{{ form.errors.status }}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="field-description">Description</label>
                                    <div class="form-control-wrap">
                                        <textarea
                                            class="form-control"
                                            id="field-description"
                                            placeholder="Enter description (optional)"
                                            v-model="form.description"
                                            rows="3"
                                        ></textarea>
                                    </div>
                                    <div v-if="form.errors.description" class="form-note text-danger">{{ form.errors.description }}</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" @click="closeModal">Cancel</button>
                    <button
                        type="button"
                        class="btn btn-primary"
                        @click="saveField"
                        :disabled="isLoading || !form.name || !form.size || !form.boundaries"
                    >
                        <div v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status"></div>
                        <em v-else class="icon ni ni-save me-1"></em>
                        <span>Save Field</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { FieldStatus } from '@/Enum';
import Swal from 'sweetalert2';
import FieldMap from '@/Components/FieldMap.vue';

export default {
    components: {
        FieldMap,
    },
    props: {
        crops: {
            type: Array,
            default: () => [],
        },
        statuses: {
            type: Array,
            default: () => [],
        },
    },
    setup(props, { emit }) {
        const showModal = ref(false);
        const isLoading = ref(false);

        const form = useForm({
            name: null,
            location: null,
            size: null,
            description: null,
            boundaries: null,
            status: FieldStatus.Active,
            crop_id: [],
        });

        const openModal = () => {
            showModal.value = true;
        };

        const closeModal = () => {
            showModal.value = false;
            form.reset();
        };

        const updateFieldSize = (area) => {
            form.size = area;
        };

        const saveField = () => {
            if (!form.boundaries) {
                Swal.fire({
                    title: 'No Field Drawn',
                    text: 'Please draw a field on the map before saving.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                });
                return;
            }

            isLoading.value = true;

            form.post(route('fields.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();

                    Swal.fire({
                        title: 'Field Created',
                        text: 'The field has been created successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                    });

                    emit('field-created');
                },
                onError: () => {
                    isLoading.value = false;
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        };

        return {
            showModal,
            form,
            openModal,
            closeModal,
            saveField,
            updateFieldSize,
            isLoading,
        };
    },
};
</script>

<style>
.modal {
    background-color: rgba(0, 0, 0, 0.4);
}
</style>
