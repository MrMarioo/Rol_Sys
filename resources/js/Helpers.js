import { router, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

function watchForm(form) {
    watch(form, (values) => {
        let parsedData = Object.keys(values.data()).reduce((a, b) => {
            if (values.data()[b] != null) {
                a[b] = values.data()[b];
            }
            return a;
        }, {});

        router.get(location.origin + location.pathname, parsedData, {
            preserveState: true,
            replace: true,
        });
    });
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

export { watchForm, auth, numberFormat, ConfirmModal, Toast };
