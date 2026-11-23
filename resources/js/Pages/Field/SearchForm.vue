<script>
import { useForm } from '@inertiajs/vue3';
import { filterParam, watchForm } from '@/Helpers';
import ResetButton from '@/Components/ResetButton.vue';

export default {
    components: {
        ResetButton,
    },
    props: {
        crops: Array,
        statuses: Array,
    },
    setup(props) {
        const form = useForm({
            name: filterParam('name'),
            status: filterParam('status'),
            crop_id: filterParam('crop_id') ? Number(filterParam('crop_id')) : null,
            size_min: filterParam('size_min'),
            size_max: filterParam('size_max'),
        });

        watchForm(form);

        return {
            form,
        };
    },
};
</script>

<template>
    <div class="nk-block-head-content">
        <div class="toggle-wrap nk-block-tools-toggle">
            <div class="toggle-expand-content expanded" data-content="pageMenu">
                <ul class="nk-block-tools g-4">
                    <li>
                        <div class="form-control-wrap">
                            <div class="form-icon form-icon-left">
                                <em class="icon ni ni-search"></em>
                            </div>
                            <input type="text" class="form-control" placeholder="Search by name" v-model="form.name" />
                        </div>
                    </li>
                    <li>
                        <div class="form-control-wrap">
                            <select class="form-select" v-model="form.status">
                                <option :value="null">All statuses</option>
                                <option v-for="stat in statuses" :key="stat" :value="stat">
                                    {{ stat === 'active' ? 'Active' : stat === 'inactive' ? 'Inactive' : stat }}
                                </option>
                            </select>
                        </div>
                    </li>
                    <li>
                        <div class="form-control-wrap">
                            <select class="form-select" v-model="form.crop_id">
                                <option :value="null">All crops</option>
                                <option v-for="crop in crops" :key="crop.id" :value="crop.id">
                                    {{ crop.name }}
                                </option>
                            </select>
                        </div>
                    </li>
                    <li>
                        <div class="form-control-wrap">
                            <input type="number" class="form-control" placeholder="Min size" v-model="form.size_min" min="0" step="0.1" />
                        </div>
                    </li>
                    <li>
                        <div class="form-control-wrap">
                            <input type="number" class="form-control" placeholder="Max size" v-model="form.size_max" min="0" step="0.1" />
                        </div>
                    </li>
                    <li class="nk-block-tools-opt">
                        <ResetButton :form="form" />
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
