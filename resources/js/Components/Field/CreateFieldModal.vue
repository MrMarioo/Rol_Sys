<template>
    <div
        class="modal fade modal-backdrop-tint"
        :class="{ 'show d-block': showModal }"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-field-title"
        :aria-hidden="!showModal"
    >
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="create-field-title" class="modal-title">Add New Field</h5>
                    <button type="button" class="close" aria-label="Close" @click="closeModal">
                        <em class="icon ni ni-cross"></em>
                    </button>
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
                                    <label class="form-label" for="create-field-name">
                                        Field Name
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="create-field-name"
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
                                    <label class="form-label" for="create-field-location">Location</label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="create-field-location"
                                            placeholder="Enter location (optional)"
                                            v-model="form.location"
                                        />
                                    </div>
                                    <div v-if="form.errors.location" class="form-note text-danger">{{ form.errors.location }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="create-field-size">
                                        Size (hectares)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <input
                                            type="number"
                                            class="form-control"
                                            id="create-field-size"
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
                                    <label class="form-label" for="create-field-status">
                                        Status
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-control-wrap">
                                        <select class="form-select" id="create-field-status" v-model="form.status" required>
                                            <option v-for="status in statuses" :key="status" :value="status">
                                                {{ formatLabel(status) }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.errors.status" class="form-note text-danger">{{ form.errors.status }}</div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="create-field-description">Description</label>
                                    <div class="form-control-wrap">
                                        <textarea
                                            class="form-control"
                                            id="create-field-description"
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
                        :disabled="form.processing || !form.name || !form.size || !form.boundaries"
                    >
                        <div v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></div>
                        <em v-else class="icon ni ni-save me-1"></em>
                        <span>Save Field</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FieldStatus } from '@/Enum';
import { Toast } from '@/Helpers';
import { formatLabel } from '@/format';
import FieldMap from '@/Components/FieldMap.vue';

defineProps({
    crops: {
        type: Array,
        default: () => [],
    },
    statuses: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);

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
    form.clearErrors();
};

const updateFieldSize = (area) => {
    form.size = area;
};

const saveField = () => {
    if (!form.boundaries) {
        Toast.fire({ icon: 'warning', title: 'Please draw a field on the map before saving.' });
        return;
    }

    form.post(route('fields.store'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

defineExpose({ openModal });
</script>

<style scoped>
.modal-backdrop-tint {
    background-color: rgba(0, 0, 0, 0.4);
}
</style>
