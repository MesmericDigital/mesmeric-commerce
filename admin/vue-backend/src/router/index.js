import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '@/views/DashboardView.vue';
import ModulesView from '@/views/ModulesView.vue';
import SettingsView from '@/views/SettingsView.vue';
import LogsView from '@/views/LogsView.vue';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'dashboard',
            component: DashboardView,
        },
        {
            path: '/modules',
            name: 'modules',
            component: ModulesView,
        },
        {
            path: '/settings',
            name: 'settings',
            component: SettingsView,
        },
        {
            path: '/logs',
            name: 'logs',
            component: LogsView,
        },
    ],
});

export default router;
