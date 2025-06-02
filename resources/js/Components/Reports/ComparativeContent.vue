<template>
    <div class="comparative-report">
        <!-- Comparison Type Info -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="alert alert-light">
                    <h6 class="mb-2">Comparative Analysis</h6>
                    <p class="mb-0">
                        Comparison Type:
                        <span class="badge badge-outline-primary ms-1">
                            {{ formatComparisonType(content.comparison_type) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Fields Comparison -->
        <div v-if="content.comparison_type === 'fields'">
            <div class="card">
                <div class="card-inner">
                    <h5 class="card-title">Field Performance Comparison</h5>

                    <!-- Placeholder for fields comparison -->
                    <div class="alert alert-info">
                        <h6 class="mb-2">
                            <em class="icon ni ni-info-circle"></em>
                            Field Comparison Analysis
                        </h6>
                        <p class="mb-3">This section would compare the following metrics across selected fields:</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li>Average NDVI values</li>
                                    <li>Soil moisture levels</li>
                                    <li>Crop yield performance</li>
                                    <li>Problem occurrence rates</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="mb-0">
                                    <li>Resource utilization efficiency</li>
                                    <li>Weather impact resilience</li>
                                    <li>Growth stage progression</li>
                                    <li>Overall field health scores</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Sample comparison table -->
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Field A</th>
                                <th>Field B</th>
                                <th>Field C</th>
                                <th>Best Performer</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="fw-bold">Average NDVI</td>
                                <td>0.72</td>
                                <td>0.68</td>
                                <td>0.75</td>
                                <td><span class="badge badge-success">Field C</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Soil Moisture</td>
                                <td>0.45</td>
                                <td>0.52</td>
                                <td>0.48</td>
                                <td><span class="badge badge-success">Field B</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Problem Count</td>
                                <td>2</td>
                                <td>5</td>
                                <td>1</td>
                                <td><span class="badge badge-success">Field C</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Data Collections</td>
                                <td>28</td>
                                <td>25</td>
                                <td>30</td>
                                <td><span class="badge badge-success">Field C</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Field Performance Chart Placeholder -->
            <div class="card mt-4">
                <div class="card-inner">
                    <h5 class="card-title">Performance Trends</h5>
                    <div class="text-center py-5">
                        <em class="icon ni ni-bar-chart" style="font-size: 3rem; color: #c4c4c4;"></em>
                        <h6 class="mt-3 mb-2">Performance Chart</h6>
                        <p class="text-muted">Interactive chart comparing field performance over time would be displayed here</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Periods Comparison -->
        <div v-else-if="content.comparison_type === 'time_periods'">
            <div class="card">
                <div class="card-inner">
                    <h5 class="card-title">Time Period Comparison</h5>

                    <div class="alert alert-info">
                        <h6 class="mb-2">
                            <em class="icon ni ni-calendar"></em>
                            Period-over-Period Analysis
                        </h6>
                        <p class="mb-3">This section would compare the same fields across different time periods:</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6>Current Period Metrics:</h6>
                                <ul class="mb-0">
                                    <li>Total data collections: 85</li>
                                    <li>Average NDVI: 0.72</li>
                                    <li>Average moisture: 0.48</li>
                                    <li>Problems detected: 8</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Previous Period Metrics:</h6>
                                <ul class="mb-0">
                                    <li>Total data collections: 78</li>
                                    <li>Average NDVI: 0.69</li>
                                    <li>Average moisture: 0.52</li>
                                    <li>Problems detected: 12</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Period comparison table -->
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                            <tr>
                                <th>Metric</th>
                                <th>Current Period</th>
                                <th>Previous Period</th>
                                <th>Change</th>
                                <th>Trend</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td class="fw-bold">Total Collections</td>
                                <td>85</td>
                                <td>78</td>
                                <td>+7 (+9%)</td>
                                <td><span class="badge badge-success">↑ Improving</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Average NDVI</td>
                                <td>0.72</td>
                                <td>0.69</td>
                                <td>+0.03 (+4%)</td>
                                <td><span class="badge badge-success">↑ Improving</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Average Moisture</td>
                                <td>0.48</td>
                                <td>0.52</td>
                                <td>-0.04 (-8%)</td>
                                <td><span class="badge badge-warning">↓ Declining</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold">Problems Detected</td>
                                <td>8</td>
                                <td>12</td>
                                <td>-4 (-33%)</td>
                                <td><span class="badge badge-success">↓ Improving</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Trend Analysis -->
            <div class="card mt-4">
                <div class="card-inner">
                    <h5 class="card-title">Trend Analysis</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="alert alert-success">
                                <h6 class="mb-2">
                                    <em class="icon ni ni-trend-up"></em>
                                    Positive Trends
                                </h6>
                                <ul class="mb-0">
                                    <li>NDVI values are improving (+4%)</li>
                                    <li>Data collection frequency increased (+9%)</li>
                                    <li>Fewer problems detected (-33%)</li>
                                    <li>Overall field health is trending upward</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <h6 class="mb-2">
                                    <em class="icon ni ni-trend-down"></em>
                                    Areas for Attention
                                </h6>
                                <ul class="mb-0">
                                    <li>Soil moisture levels declining (-8%)</li>
                                    <li>Consider irrigation system review</li>
                                    <li>Monitor weather patterns impact</li>
                                    <li>Plan for seasonal moisture variations</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insights & Recommendations -->
        <div class="card mt-4">
            <div class="card-inner">
                <h5 class="card-title">Key Insights & Recommendations</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-light">
                            <div class="card-inner">
                                <h6 class="card-title">
                                    <em class="icon ni ni-bulb text-warning"></em>
                                    Key Insights
                                </h6>
                                <ul class="mb-0">
                                    <li v-if="content.comparison_type === 'fields'">
                                        Field performance varies significantly across locations
                                    </li>
                                    <li v-else>
                                        Overall farm performance is showing positive trends
                                    </li>
                                    <li>Data collection consistency directly impacts analysis quality</li>
                                    <li>Early problem detection systems are proving effective</li>
                                    <li>Seasonal variations should be factored into comparisons</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-light">
                            <div class="card-inner">
                                <h6 class="card-title">
                                    <em class="icon ni ni-check-circle text-success"></em>
                                    Action Items
                                </h6>
                                <ul class="mb-0">
                                    <li v-if="content.comparison_type === 'fields'">
                                        Apply best practices from top-performing fields
                                    </li>
                                    <li v-else>
                                        Continue current management strategies
                                    </li>
                                    <li>Standardize data collection protocols</li>
                                    <li>Implement targeted interventions for underperforming areas</li>
                                    <li>Schedule regular comparative analysis reviews</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Implementation Note -->
        <div class="alert alert-light mt-4">
            <h6 class="mb-2">
                <em class="icon ni ni-info"></em>
                Implementation Note
            </h6>
            <p class="mb-0">
                This comparative analysis framework provides the structure for detailed field and temporal comparisons.
                The actual data comparison logic would integrate with your existing analytics system to provide
                real-time comparative insights based on your specific field data and metrics.
            </p>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        content: {
            type: Object,
            required: true
        }
    },
    setup() {
        const formatComparisonType = (type) => {
            const types = {
                'fields': 'Field-to-Field Comparison',
                'time_periods': 'Time Period Comparison'
            };
            return types[type] || type;
        };

        return {
            formatComparisonType
        };
    }
};
</script>

<style scoped>
.alert h6 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert ul {
    padding-left: 1.25rem;
}

.alert li {
    margin-bottom: 0.25rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
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

.text-center .icon {
    display: block;
    margin: 0 auto 1rem;
}
</style>
