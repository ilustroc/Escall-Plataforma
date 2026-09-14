import '../css/app.css';
import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
    title: (title) => `${title} · Plataforma ESCALL`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        if (!pages[`./Pages/${name}.vue`]) throw new Error(`Página no disponible: ${name}`);
        return pages[`./Pages/${name}.vue`]();
    },
    setup: ({ el, App, props, plugin }) => createApp({ render: () => h(App, props) }).use(plugin).mount(el),
    progress: { color: '#155EEF', showSpinner: true },
});
