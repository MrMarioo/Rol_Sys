<template>
    <Head :title="`Edit ${field.name}`" />
    <div>
        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Edit Field</h3>
                    <div class="nk-block-des text-soft">
                        <Link :href="route('fields.index')" class="btn btn-outline-primary btn-sm">
                            <em class="icon ni ni-arrow-left me-1"></em>
                            Back to Fields
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <div class="card">
                <div class="card-inner">
                    <form @submit.prevent="saveField">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">Edit Field Boundaries</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <FieldMap
                                            mode="edit"
                                            height="500px"
                                            :boundaries="form.boundaries"
                                            @update:boundaries="form.boundaries = $event"
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
                                                {{ formatLabel(status) }}
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

                            <div class="col-12">
                                <div class="form-group mt-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <Link :href="route('fields.show', field.id)" class="btn btn-outline-secondary p-3">Cancel</Link>
                                        <button
                                            type="submit"
                                            class="btn btn-primary p-3"
                                            :disabled="form.processing || !form.name || !form.size || !form.boundaries"
                                        >
                                            <div v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></div>
                                            <em v-else class="icon ni ni-save me-1"></em>
                                            <span>Update Field</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FieldStatus } from '@/Enum';
import { Toast } from '@/Helpers';
import { formatLabel } from '@/format';
import FieldMap from '@/Components/FieldMap.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    field: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    id: props.field.id,
    name: props.field.name,
    location: props.field.location,
    size: props.field.size,
    description: props.field.description,
    boundaries: props.field.boundaries,
    status: props.field.status || FieldStatus.Active,
    crop_id: props.field.crop_id || [],
    _method: 'PUT',
});

const updateFieldSize = (area) => {
    form.size = area;
};

const saveField = () => {
    if (!form.boundaries) {
        Toast.fire({ icon: 'warning', title: 'Please ensure the field has valid boundaries.' });
        return;
    }

    form.post(route('fields.update', props.field.id), {
        preserveScroll: true,
    });
};
</script>
