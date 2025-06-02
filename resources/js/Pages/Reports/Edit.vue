<template>
    <div>
        <Head title="Edit Report" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Edit Report</h3>
                    <div class="nk-block-des text-soft">
                        <Link :href="route('reports.show', report.id)" class="btn btn-outline-primary btn-sm">
                            <em class="icon ni ni-arrow-left me-1"></em>
                            Back to Report
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <form @submit.prevent="updateReport">
                <div class="row g-4">
                    <!-- Template Display (Read-only) -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-inner">
                                <h5 class="card-title">Report Template</h5>
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center">
                                        <em class="icon ni" :class="currentTemplate.icon"></em>
                                        <div class="ms-3">
                                            <h6 class="mb-1">{{ currentTemplate.name }}</h6>
                                            <p class="mb-0">{{ currentTemplate.description }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-note">
                                    <em class="icon ni ni-info"></em>
                                    Report template cannot be changed after creation. Create a new report to use a different template.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Details -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-inner">
                                <h5 class="card-title">Report Details</h5>

                                <div class="form-group">
                                    <label class="form-label" for="title">Report Title <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="title"
                                        v-model="form.title"
                                        placeholder="Enter report title"
                                        required
                                    />
                                    <div v-if="form.errors.title" class="form-note text-danger">{{ form.errors.title }}</div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="description">Description</label>
                                    <textarea
                                        class="form-control"
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Optional description"
                                        rows="3"
                                    ></textarea>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Select Fields <span class="text-danger">*</span></label>
                                    <div class="form-control-wrap">
                                        <select class="form-select" v-model="form.fields_included" multiple>
                                            <option v-for="field in fields" :key="field.id" :value="field.id">
                                                {{ field.name }} ({{ field.size }} ha)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-note">Hold Ctrl/Cmd to select multiple fields</div>
                                    <div v-if="form.errors.fields_included" class="form-note text-danger">{{ form.errors.fields_included }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Parameters -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <h5 class="card-title">Parameters</h5>

                                <!-- Weekly/Monthly Summary Parameters -->
                                <div v-if="report.type === 'weekly_summary' || report.type === 'monthly_summary'">
                                    <div v-if="report.type === 'weekly_summary'" class="form-group">
                                        <label class="form-label">Start Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.parameters.start_date"
                                        />
                                    </div>
                                    <div v-if="report.type === 'weekly_summary'" class="form-group">
                                        <label class="form-label">End Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.parameters.end_date"
                                        />
                                    </div>

                                    <div v-if="report.type === 'monthly_summary'" class="form-group">
                                        <label class="form-label">Month</label>
                                        <select class="form-select" v-model="form.parameters.month">
                                            <option v-for="n in 12" :key="n" :value="n">
                                                {{ getMonthName(n) }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="report.type === 'monthly_summary'" class="form-group">
                                        <label class="form-label">Year</label>
                                        <select class="form-select" v-model="form.parameters.year">
                                            <option v-for="year in years" :key="year" :value="year">
                                                {{ year }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Problem Report Parameters -->
                                <div v-if="report.type === 'problem_report'">
                                    <div class="form-group">
                                        <label class="form-label">Severity Level</label>
                                        <select class="form-select" v-model="form.parameters.severity">
                                            <option value="all">All Levels</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">From Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.parameters.start_date"
                                        />
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">To Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.parameters.end_date"
                                        />
                                    </div>
                                </div>

                                <!-- Crop Status Parameters -->
                                <div v-if="report.type === 'crop_status'">
                                    <div class="form-group">
                                        <label class="form-label">Growth Stage</label>
                                        <select class="form-select" v-model="form.parameters.growth_stage">
                                            <option value="all">All Stages</option>
                                            <option value="germination">Germination</option>
                                            <option value="seedling">Seedling</option>
                                            <option value="vegetative">Vegetative</option>
                                            <option value="flowering">Flowering</option>
                                            <option value="maturation">Maturation</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Comparative Parameters -->
                                <div v-if="report.type === 'comparative'">
                                    <div class="form-group">
                                        <label class="form-label">Comparison Type</label>
                                        <select class="form-select" v-model="form.parameters.comparison_type">
                                            <option value="fields">Compare Fields</option>
                                            <option value="time_periods">Compare Time Periods</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="alert alert-warning mt-3">
                                    <em class="icon ni ni-alert-circle"></em>
                                    Changing parameters will require regenerating the report content.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="col-12">
                        <div class="form-group mt-4">
                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('reports.show', report.id)" class="btn btn-outline-secondary p-3">Cancel</Link>
                                <button
                                    type="submit"
                                    class="btn btn-primary p-3"
                                    :disabled="isLoading || !form.title || form.fields_included.length === 0"
                                >
                                    <div v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status"></div>
                                    <em v-else class="icon ni ni-save me-1"></em>
                                    <span>Update Report</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { useForm, Link, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
    components: {
        Link,
        Head,
    },
    props: {
        report: Object,
        templates: Object,
        fields: Array,
        crops: Array,
    },
    setup(props) {
        const isLoading = ref(false);

        const form = useForm({
            title: props.report.title,
            type: props.report.type,
            description: props.report.description || '',
            fields_included: props.report.fields_included || [],
            parameters: props.report.parameters || {}
        });

        const years = computed(() => {
            const currentYear = new Date().getFullYear();
            return Array.from({ length: 5 }, (_, i) => currentYear - i);
        });

        const currentTemplate = computed(() => {
            return props.templates[props.report.type] || {
                name: 'Unknown Template',
                description: 'Template information not available',
                icon: 'ni-file-text'
            };
        });

        const getMonthName = (month) => {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return months[month - 1];
        };

        const updateReport = () => {
            isLoading.value = true;

            form.put(route('reports.update', props.report.id), {
                preserveScroll: true,
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        };

        return {
            form,
            isLoading,
            years,
            currentTemplate,
            getMonthName,
            updateReport,
        };
    },
};
</script>

<style scoped>
.alert {
    border-radius: 0.5rem;
    padding: 1rem 1.25rem;
}

.alert-info {
    background-color: #f8f9ff;
    border-color: #6576ff;
    color: #364a63;
}

.alert-warning {
    background-color: #fffdf8;
    border-color: #f4bd0e;
    color: #364a63;
}

.alert .icon {
    font-size: 1.25rem;
    margin-right: 0.5rem;
}

.form-note .icon {
    font-size: 0.875rem;
    margin-right: 0.25rem;
}
</style>
