import './bootstrap.js'
import './custom.js'
import '../css/bootstrap.css'
import '../css/tiny-slider.css'
import '../css/style.css'
import '../css/fonts/flaticon/font/flaticon.css'
import '../css/fonts/icomoon/style.css'
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import '../css/app.css';
import '@fortawesome/fontawesome-free/css/all.css'


import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import Layout from '@/Pages/Layout.vue';
import { InertiaProgress } from '@inertiajs/progress'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))
            .then((page) => {
                page.default.layout ??= Layout
                return page
            })
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
})

// Indicateur de chargement Inertia
InertiaProgress.init({
    color: '#4B5563',
    showSpinner: true,
})
