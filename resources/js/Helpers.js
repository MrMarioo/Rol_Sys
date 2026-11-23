import { router, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

function watchForm(form) {
    watch(form, (values) => {
        const activeFilters = Object.fromEntries(
            Object.entries(values.data()).filter(([, value]) => value !== null && value !== ''),
        );

        router.get(location.origin + location.pathname, { filter: activeFilters }, {
            preserveState: true,
            replace: true,
        });
    });
}
function filterParam(name) {
    return new URLSearchParams(location.search).get(`filter[${name}]`);
}
function auth() {
    return usePage().props.auth.user;
}
function numberFormat(number) {
    return number.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
const ConfirmModal = Swal.mixin({
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Yes',
    cancelButtonText: 'No, cancel',
    confirmButtonColor: '#3085d6',
});
const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 5000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    },
});

export { watchForm, filterParam, auth, numberFormat, ConfirmModal, Toast };
