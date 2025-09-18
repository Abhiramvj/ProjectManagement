// resources/js/app.js
import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

// Axios defaults
if (typeof window.axios !== 'undefined') {
  window.axios.defaults.withCredentials = true
}

// Pusher + Echo setup
window.Pusher = Pusher
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: true,
  encrypted: true,
})

window.Echo.private('user-import').listen('UserImportCompleted', (e) => {
  alert(`Import completed for file: ${e.filePath}`)
})

// Clear flash messages after each Inertia navigation
router.on('finish', () => {
  if (router.page?.props?.flash) {
    router.page.props.flash.success = null
    router.page.props.flash.error = null
  }
})

const appName =
  window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel'

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    let page = pages[`./Pages/${name}.vue`]

    if (!page) {
      // Fallback to dynamic import if eager loading fails
      return resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
      )
    }

    return page
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({
      render: () => h(App, props),
      // Add error handler
      errorCaptured(err, instance, info) {
        console.error('Vue error captured:', err, info)
        return false
      }
    })

    // Install plugins
    app.use(plugin)
    app.use(ZiggyVue)

    // Global error handler
    app.config.errorHandler = (err, instance, info) => {
      console.error('Global Vue error:', err, info)
    }

    return app.mount(el)
  },
  progress: {
    color: '#4B5563',
    delay: 250,
    includeCSS: true,
    showSpinner: false,
  },
})
