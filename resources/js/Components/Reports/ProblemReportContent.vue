<template>
    <div class="problem-report">
        <!-- Period Info -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="alert alert-light">
                    <h6 class="mb-2">{{ content.period.type }}</h6>
                    <p class="mb-0">
                        Period: {{ formatDate(content.period.start) }} - {{ formatDate(content.period.end) }}
                        <span v-if="content.severity_filter !== 'all'" class="ms-2">
                            | Severity: <span class="badge badge-outline-primary">{{ content.severity_filter }}</span>
                        </span>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-inner text-center">
                        <div class="nk-iv-wg2-title">
                            <em class="icon ni ni-alert-circle text-warning"></em>
                        </div>
                        <h4 class="title">{{ content.total_problems }}</h4>
                        <p class="sub-text">Total Problems</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Problems Found -->
        <div v-if="content.total_problems === 0" class="card">
            <div class="card-inner text-center py-5">
                <div class="nk-data-empty">
                    <div class="nk-data-empty-icon">
                        <em class="icon ni ni-check-circle text-success"></em>
                    </div>
                    <h5 class="nk-data-empty-title text-success">No Problems Detected</h5>
                    <p class="nk-data-empty-note">
                        All fields are operating within normal parameters for the selected period and severity level.
                    </p>
                </div>
            </div>
        </div>

        <!-- Problems by Field -->
        <div v-else class="row g-4">
            <div v-for="(fieldProblems, fieldId) in content.problems" :key="fieldId" class="col-12">
                <div class="card">
                    <div class="card-inner">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">{{ fieldProblems.field_name }}</h5>
                            <span class="badge badge-outline-warning">
                                {{ fieldProblems.problem_count }} {{ fieldProblems.problem_count === 1 ? 'issue' : 'issues' }}
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Severity</th>
                                    <th>Description</th>
                                    <th>Location</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="problem in fieldProblems.problems" :key="problem.date + problem.type">
                                    <td>{{ formatDate(problem.date) }}</td>
                                    <td>
                                            <span class="badge badge-outline-info">
                                                {{ formatProblemType(problem.type) }}
                                            </span>
                                    </td>
                                    <td>
                                            <span :class="getSeverityBadgeClass(problem.severity)">
                                                <em class="icon ni" :class="getSeverityIcon(problem.severity)"></em>
                                                {{ problem.severity }}
                                            </span>
                                    </td>
                                    <td>{{ problem.description }}</td>
                                    <td>{{ problem.location }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Problem Summary by Type -->
        <div v-if="content.total_problems > 0" class="card mt-4">
            <div class="card-inner">
                <h5 class="card-title">Problem Summary by Type</h5>
                <div class="row g-3">
                    <div v-for="(count, type) in problemsByType" :key="type" class="col-md-4">
                        <div class="card border-light">
                            <div class="card-inner text-center">
                                <em class="icon ni ni-alert-circle text-warning mb-2" style="font-size: 1.5rem;"></em>
                                <h6>{{ formatProblemType(type) }}</h6>
                                <p class="text-soft">{{ count }} {{ count === 1 ? 'occurrence' : 'occurrences' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div v-if="content.total_problems > 0" class="card mt-4">
            <div class="card-inner">
                <h5 class="card-title">Recommended Actions</h5>
                <div class="alert alert-info">
                    <h6 class="mb-2">Immediate Actions Required:</h6>
                    <ul class="mb-3">
                        <li v-if="hasHighSeverityProblems">
                            <strong>High Priority:</strong> Investigate and address high-severity issues immediately
                        </li>
                        <li v-if="hasNdviAnomalies">
                            <strong>NDVI Issues:</strong> Check crop health and consider fertilization or pest control
                        </li>
                        <li v-if="hasMoistureAnomalies">
                            <strong>Moisture Issues:</strong> Review irrigation system and soil drainage
                        </li>
                    </ul>
                    <h6 class="mb-2">Preventive Measures:</h6>
                    <ul class="mb-0">
                        <li>Increase monitoring frequency for affected fields</li>
                        <li>Schedule regular equipment maintenance</li>
                        <li>Consider implementing additional sensors in problem areas</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue';

export default {
    props: {
        content: {
            type: Object,
            required: true
        }
    },
    setup(props) {
        const problemsByType = computed(() => {
            const types = {};
            Object.values(props.content.problems).forEach(fieldProblems => {
                fieldProblems.problems.forEach(problem => {
                    types[problem.type] = (types[problem.type] || 0) + 1;
                });
            });
            return types;
        });

        const hasHighSeverityProblems = computed(() => {
            return Object.values(props.content.problems).some(fieldProblems =>
                fieldProblems.problems.some(problem => problem.severity === 'high')
            );
        });

        const hasNdviAnomalies = computed(() => {
            return Object.values(props.content.problems).some(fieldProblems =>
                fieldProblems.problems.some(problem => problem.type.includes('ndvi'))
            );
        });

        const hasMoistureAnomalies = computed(() => {
            return Object.values(props.content.problems).some(fieldProblems =>
                fieldProblems.problems.some(problem => problem.type.includes('moisture'))
            );
        });

        const formatDate = (dateString) => {
            return new Date(dateString).toLocaleDateString();
        };

        const formatProblemType = (type) => {
            return type.replace(/_/g, ' ')
                .replace(/\b\w/g, l => l.toUpperCase())
                .replace(' Anomaly', '');
        };

        const getSeverityBadgeClass = (severity) => {
            const classes = {
                'low': 'badge badge-outline-info',
                'medium': 'badge badge-outline-warning',
                'high': 'badge badge-outline-danger'
            };
            return classes[severity] || 'badge badge-outline-secondary';
        };

        const getSeverityIcon = (severity) => {
            const icons = {
                'low': 'ni-info',
                'medium': 'ni-alert-triangle',
                'high': 'ni-alert-circle'
            };
            return icons[severity] || 'ni-help';
        };

        return {
            problemsByType,
            hasHighSeverityProblems,
            hasNdviAnomalies,
            hasMoistureAnomalies,
            formatDate,
            formatProblemType,
            getSeverityBadgeClass,
            getSeverityIcon
        };
    }
};
</script>

<style scoped>
.nk-iv-wg2-title .icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.nk-data-empty-icon .icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.card.border-light {
    border-color: #e5e9f2 !important;
}

.table th {
    font-weight: 600;
    color: #364a63;
    border-bottom: 2px solid #e5e9f2;
}

.table td {
    vertical-align: middle;
}

.alert ul {
    padding-left: 1.25rem;
}

.alert li {
    margin-bottom: 0.5rem;
}
</style>
