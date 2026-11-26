import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '../sass/app.scss';
import 'bootstrap';

const shownFlashes = new WeakSet();

const showFlash = (flash) => {
    if (!flash || shownFlashes.has(flash) || (!flash.success && !flash.error)) return;

    shownFlashes.add(flash);

    import('./Helpers').then(({ Toast }) => {
        if (flash.success) {
            Toast.fire({ icon: 'success', title: flash.success });
        }
        if (flash.error) {
            Toast.fire({ icon: 'error', title: flash.error });
        }
    });
};

router.on('success', (event) => showFlash(event.detail.page.props.flash));

createInertiaApp({
    title: (title) => `${title} - RolSys`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        showFlash(props.initialPage.props.flash);

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({ methods: { route } })
            .mount(el);
    },
});
