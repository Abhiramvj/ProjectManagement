import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router, usePage } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    encrypted: true,
});

window.Echo.private('user-import')
    .listen('UserImportCompleted', (e) => {
        // For example, show a toast notification here
        alert(`Import completed for file: ${e.filePath}`);
        // Or trigger a Vue state update or event bus emit
    });

// Global Inertia event listener: clear flash messages after each Inertia navigation
router.on('finish', () => {
    const page = usePage();
    if (page.props.flash) {
        page.props.flash.success = null;
        page.props.flash.error = null;
    }
});

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Vue page not found: The page component for '${name}' was not found at './Pages/${name}.vue'.`);
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
        delay: 250,
        includeCSS: true,
        showSpinner: false,
    },
});
