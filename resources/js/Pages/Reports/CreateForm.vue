<template>
    <div>
        <Head title="Create Report" />

        <div class="nk-block-head nk-block-head-sm">
            <div class="nk-block-between">
                <div class="nk-block-head-content">
                    <h3 class="nk-block-title page-title">Create New Report</h3>
                    <div class="nk-block-des text-soft">
                        <Link :href="route('reports.index')" class="btn btn-outline-primary btn-sm">
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
                    <div class="col-12">
                        <div class="card">
                            <div class="card-inner">
                                <h5 class="card-title">Select Report Template</h5>
                                <div class="row g-3">
                                    <div v-for="(template, key) in templates" :key="key" class="col-md-6 col-lg-4">
                                        <button
                                            type="button"
                                            class="template-option"
                                            :class="{ active: form.type === key }"
                                            :aria-pressed="form.type === key"
                                            @click="selectTemplate(key)"
                                        >
                                            <span class="template-icon d-block">
                                                <em class="icon ni" :class="template.icon"></em>
                                            </span>
                                            <span class="h6 d-block">{{ template.name }}</span>
                                            <span class="template-description d-block">{{ template.description }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    <label class="form-label" for="fields_included">Select Fields <span class="text-danger">*</span></label>
                                    <div class="form-control-wrap">
                                        <select id="fields_included" v-model="form.fields_included" class="form-select" multiple>
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

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-inner">
                                <h5 class="card-title">Parameters</h5>

                                <div v-if="form.type === 'weekly_summary' || form.type === 'monthly_summary'">
                                    <div v-if="form.type === 'weekly_summary'" class="form-group">
                                        <label class="form-label" for="start_date">Start Date</label>
                                        <input id="start_date" v-model="form.parameters.start_date" type="date" class="form-control" />
                                    </div>
                                    <div v-if="form.type === 'weekly_summary'" class="form-group">
                                        <label class="form-label" for="end_date">End Date</label>
                                        <input id="end_date" v-model="form.parameters.end_date" type="date" class="form-control" />
                                    </div>

                                    <div v-if="form.type === 'monthly_summary'" class="form-group">
                                        <label class="form-label" for="month">Month</label>
                                        <select id="month" v-model="form.parameters.month" class="form-select">
                                            <option v-for="n in 12" :key="n" :value="n">
                                                {{ MONTHS[n - 1] }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.type === 'monthly_summary'" class="form-group">
                                        <label class="form-label" for="year">Year</label>
                                        <select id="year" v-model="form.parameters.year" class="form-select">
                                            <option v-for="year in years" :key="year" :value="year">
                                                {{ year }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div v-if="form.type === 'problem_report'">
                                    <div class="form-group">
                                        <label class="form-label" for="severity">Severity Level</label>
                                        <select id="severity" v-model="form.parameters.severity" class="form-select">
                                            <option value="all">All Levels</option>
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="start_date">From Date</label>
                                        <input id="start_date" v-model="form.parameters.start_date" type="date" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="end_date">To Date</label>
                                        <input id="end_date" v-model="form.parameters.end_date" type="date" class="form-control" />
                                    </div>
                                </div>

                                <div v-if="form.type === 'crop_status'">
                                    <div class="form-group">
                                        <label class="form-label" for="growth_stage">Growth Stage</label>
                                        <select id="growth_stage" v-model="form.parameters.growth_stage" class="form-select">
                                            <option value="all">All Stages</option>
                                            <option value="germination">Germination</option>
                                            <option value="seedling">Seedling</option>
                                            <option value="vegetative">Vegetative</option>
                                            <option value="flowering">Flowering</option>
                                            <option value="maturation">Maturation</option>
                                        </select>
                                    </div>
                                </div>

                                <div v-if="form.type === 'comparative'">
                                    <div class="form-group">
                                        <label class="form-label" for="comparison_type">Comparison Type</label>
                                        <select id="comparison_type" v-model="form.parameters.comparison_type" class="form-select">
                                            <option value="fields">Compare Fields</option>
                                            <option value="time_periods">Compare Time Periods</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="start_date">From Date</label>
                                        <input id="start_date" v-model="form.parameters.start_date" type="date" class="form-control" />
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="end_date">To Date</label>
                                        <input id="end_date" v-model="form.parameters.end_date" type="date" class="form-control" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group mt-4">
                            <div class="d-flex justify-content-end gap-2">
                                <Link :href="route('reports.index')" class="btn btn-outline-secondary p-3">Cancel</Link>
                                <button
                                    type="submit"
                                    class="btn btn-primary p-3"
                                    :disabled="form.processing || !form.title || !form.type || form.fields_included.length === 0"
                                >
                                    <div v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status"></div>
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

<script setup>
import { onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDate } from '@/format';

defineOptions({ layout: AppLayout });

const props = defineProps({
    selectedTemplate: String,
    templates: Object,
    fields: Array,
    crops: Array,
});

const MONTHS = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const currentYear = new Date().getFullYear();

const years = Array.from({ length: 5 }, (_, index) => currentYear - index);

const toIsoDate = (date) => date.toISOString().split('T')[0];

const form = useForm({
    title: '',
    type: props.selectedTemplate || 'weekly_summary',
    description: '',
    fields_included: [],
    parameters: {
        start_date: toIsoDate(new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)),
        end_date: toIsoDate(new Date()),
        month: new Date().getMonth() + 1,
        year: currentYear,
        severity: 'all',
        growth_stage: 'all',
        comparison_type: 'fields',
    },
});

const selectTemplate = (templateKey) => {
    form.type = templateKey;
    form.title = `${props.templates[templateKey].name} - ${formatDate(new Date())}`;
};

const createReport = () => {
    form.post(route('reports.store'), { preserveScroll: true });
};

onMounted(() => {
    if (props.selectedTemplate && props.templates[props.selectedTemplate]) {
        selectTemplate(props.selectedTemplate);
    }
});
</script>

<style scoped>
.template-option {
    display: block;
    width: 100%;
    height: 100%;
    padding: 1.5rem;
    background-color: transparent;
    color: inherit;
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

.template-option .h6 {
    margin-bottom: 0.5rem;
    color: #364a63;
}

.template-option .template-description {
    font-size: 0.875rem;
    color: #526484;
    margin-bottom: 0;
}
</style>
