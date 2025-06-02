<template>
    <div>
        <Head title="Create Report" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Create New Report</h3>
                    <div class="nk-block-des text-soft">
                        <Link href="/reports" class="btn btn-outline-primary btn-sm">
                            <em class="icon ni ni-arrow-left me-1"></em>
                            Back to Reports
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="nk-block">
            <form @submit.prevent="createReport">
                <div class="row g-4">
                    <!-- Template Selection -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-inner">
                                <h5 class="card-title">Select Report Template</h5>
                                <div class="row g-3">
                                    <div v-for="(template, key) in templates" :key="key" class="col-md-6 col-lg-4">
                                        <div
                                            class="template-option"
                                            :class="{ active: form.type === key }"
                                            @click="selectTemplate(key)"
                                        >
                                            <div class="template-icon">
                                                <em class="icon ni" :class="template.icon"></em>
                                            </div>
                                            <h6>{{ template.name }}</h6>
                                            <p>{{ template.description }}</p>
                                        </div>
                                    </div>
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
                                <div v-if="form.type === 'weekly_summary' || form.type === 'monthly_summary'">
                                    <div v-if="form.type === 'weekly_summary'" class="form-group">
                                        <label class="form-label">Start Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.parameters.start_date"
                                        />
                                    </div>
                                    <div v-if="form.type === 'weekly_summary'" class="form-group">
                                        <label class="form-label">End Date</label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            v-model="form.parameters.end_date"
                                        />
                                    </div>

                                    <div v-if="form.type === 'monthly_summary'" class="form-group">
                                        <label class="form-label">Month</label>
                                        <select class="form-select" v-model="form.parameters.month">
                                            <option v-for="n in 12" :key="n" :value="n">
                                                {{ getMonthName(n) }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.type === 'monthly_summary'" class="form-group">
                                        <label class="form-label">Year</label>
                                        <select class="form-select" v-model="form.parameters.year">
                                            <option v-for="year in years" :key="year" :value="year">
                                                {{ year }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Problem Report Parameters -->
                                <div v-if="form.type === 'problem_report'">
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
                                <div v-if="form.type === 'crop_status'">
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
                                <div v-if="form.type === 'comparative'">
                                    <div class="form-group">
                                        <label class="form-label">Comparison Type</label>
                                        <select class="form-select" v-model="form.parameters.comparison_type">
                                            <option value="fields">Compare Fields</option>
                                            <option value="time_periods">Compare Time Periods</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="col-12">
                        <div class="form-group mt-4">
                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('reports.index')" class="btn btn-outline-secondary p-3">Cancel</Link>
                                <button
                                    type="submit"
                                    class="btn btn-primary p-3"
                                    :disabled="isLoading || !form.title || !form.type || form.fields_included.length === 0"
                                >
                                    <div v-if="isLoading" class="spinner-border spinner-border-sm me-1" role="status"></div>
                                    <em v-else class="icon ni ni-save me-1"></em>
                                    <span>Create & Generate Report</span>
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
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
    components: {
        Link,
        Head,
    },
    props: {
        selectedTemplate: String,
        templates: Object,
        fields: Array,
        crops: Array,
    },
    setup(props) {
        const isLoading = ref(false);

        const form = useForm({
            title: '',
            type: props.selectedTemplate || 'weekly_summary',
            description: '',
            fields_included: [],
            parameters: {
                start_date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                end_date: new Date().toISOString().split('T')[0],
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear(),
                severity: 'all',
                growth_stage: 'all',
                comparison_type: 'fields'
            }
        });

        const years = computed(() => {
            const currentYear = new Date().getFullYear();
            return Array.from({ length: 5 }, (_, i) => currentYear - i);
        });

        const selectTemplate = (templateKey) => {
            form.type = templateKey;
            form.title = props.templates[templateKey].name + ' - ' + new Date().toLocaleDateString();
        };

        const getMonthName = (month) => {
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            return months[month - 1];
        };

        const createReport = () => {
            isLoading.value = true;

            form.post(route('reports.store'), {
                preserveScroll: true,
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        };

        onMounted(() => {
            if (props.selectedTemplate) {
                selectTemplate(props.selectedTemplate);
            }
        });

        return {
            form,
            isLoading,
            years,
            selectTemplate,
            getMonthName,
            createReport,
        };
    },
};
</script>

<style scoped>
.template-option {
    padding: 1.5rem;
    border: 2px solid #e5e9f2;
    border-radius: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.template-option:hover {
    border-color: #6576ff;
    transform: translateY(-2px);
}

.template-option.active {
    border-color: #6576ff;
    background-color: #f8f9ff;
}

.template-icon .icon {
    font-size: 2rem;
    color: #6576ff;
    margin-bottom: 0.5rem;
}

.template-option h6 {
    margin-bottom: 0.5rem;
    color: #364a63;
}

.template-option p {
    font-size: 0.875rem;
    color: #526484;
    margin-bottom: 0;
}
</style>
