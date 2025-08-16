import './bootstrap';

// This imports your main stylesheet.
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
// We no longer need resolvePageComponent because we are writing a clearer custom resolver.
import { ZiggyVue } from 'ziggy-js';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    // =======================================================================
    //                             THE FIX
    // This is the most important change. We replace the helper function with
    // a clear, explicit resolver that is easier to understand and debug.
    // =======================================================================
    resolve: (name) => {
        // This line uses a Vite feature to immediately load all files ending in .vue
        // from your ./Pages directory and its subdirectories.
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

        // It then constructs the exact path to the page component file.
        const path = `./Pages/${name}.vue`;
        const page = pages[path];

        // If no matching file is found in the loaded pages, it throws a clear error.
        if (!page) {
            throw new Error(`Vue page not found: The page component for '${name}' was not found at '${path}'. Check your filename casing in your controller and file system.`);
        }

        // It returns the found page component.
        return page;
    },

    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue) // Your Ziggy setup is correct.
            .mount(el);
    },
    progress: {
        color: '#4B5563',
        delay: 250,
        includeCSS: true,
        showSpinner: false,
    },
});
