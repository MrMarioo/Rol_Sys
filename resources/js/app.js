import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '../sass/dashlite/scss/dashlite.scss';
import 'bootstrap';

import OpenLayersMap from 'vue3-openlayers';
import 'vue3-openlayers/styles.css';

createInertiaApp({
    title: (title) => `${title} - RolSys`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(OpenLayersMap)
            .mixin({ methods: { route } })
            .mount(el);
    },
});
