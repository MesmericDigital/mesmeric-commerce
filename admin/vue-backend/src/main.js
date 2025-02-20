import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import MC_Dashboard from './views/MC_Dashboard.vue'
import MC_Logs from './views/MC_Logs.vue'
import MC_Modules from './views/MC_Modules.vue'
import MC_Settings from './views/MC_Settings.vue'

// Create router
const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        { path: '/', component: MC_Dashboard },
        { path: '/modules', component: MC_Modules },
        { path: '/modules/:id', component: () => import('./views/MC_ModuleDetail.vue') },
        { path: '/settings', component: MC_Settings },
        { path: '/logs', component: MC_Logs }
    ]
})

// Create i18n instance
const i18n = createI18n({
    locale: window.mcAdminData?.locale || 'en',
    fallbackLocale: 'en',
    messages: window.mcAdminData?.translations || {}
})

// Create and mount app
const app = createApp(App)
app.use(router)
app.use(i18n)

// Mount app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const mountPoint = document.createElement('div')
    mountPoint.id = 'mc-admin-app'
    document.querySelector('.wrap').appendChild(mountPoint)
    app.mount('#mc-admin-app')
})
