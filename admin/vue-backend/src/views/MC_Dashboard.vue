<!-- MC_Dashboard.vue - Dashboard component for Mesmeric Commerce -->
<template>
    <div class="mc-dashboard">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Dashboard') }}</h1>
            <button class="btn btn-ghost" :disabled="isLoading" @click="refreshData">
                <span v-if="!isLoading">{{ __('Refresh') }}</span>
                <span v-else>{{ __('Loading...') }}</span>
            </button>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat bg-base-100 shadow-xl rounded-lg">
                <div class="stat-title">{{ __('Active Modules') }}</div>
                <div class="stat-value">{{ stats.activeModules }}</div>
                <div class="stat-desc">
                    {{ __('of') }} {{ stats.totalModules }} {{ __('total') }}
                </div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-lg">
                <div class="stat-title">{{ __('Recent Orders') }}</div>
                <div class="stat-value">{{ stats.recentOrders }}</div>
                <div class="stat-desc">{{ __('in last 24 hours') }}</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-lg">
                <div class="stat-title">{{ __('System Status') }}</div>
                <div class="stat-value text-success">{{ stats.systemStatus }}</div>
                <div class="stat-desc">{{ stats.systemMessage }}</div>
            </div>
            <div class="stat bg-base-100 shadow-xl rounded-lg">
                <div class="stat-title">{{ __('Cache Status') }}</div>
                <div class="stat-value">{{ stats.cacheHitRate }}%</div>
                <div class="stat-desc">{{ __('hit rate') }}</div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card bg-base-100 shadow-xl mb-8">
            <div class="card-body">
                <h2 class="card-title mb-4">{{ __('Recent Activity') }}</h2>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>{{ __('Time') }}</th>
                                <th>{{ __('Event') }}</th>
                                <th>{{ __('Module') }}</th>
                                <th>{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading">
                                <td colspan="4" class="text-center py-4">{{ __('Loading...') }}</td>
                            </tr>
                            <tr v-else-if="!activity.length">
                                <td colspan="4" class="text-center py-4">
                                    {{ __('No recent activity') }}
                                </td>
                            </tr>
                            <tr v-for="event in activity" :key="event.id">
                                <td>{{ formatDate(event.timestamp) }}</td>
                                <td>
                                    <span class="badge" :class="getEventBadgeClass(event.type)">
                                        {{ event.type }}
                                    </span>
                                </td>
                                <td>{{ event.module }}</td>
                                <td>{{ event.details }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Module Status -->
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title mb-4">{{ __('Module Status') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="module in modules"
                        :key="module.id"
                        class="flex items-center justify-between p-4 bg-base-200 rounded-lg"
                    >
                        <div>
                            <h3 class="font-medium">{{ module.name }}</h3>
                            <p class="text-sm text-gray-500">{{ module.description }}</p>
                        </div>
                        <div class="badge" :class="getModuleBadgeClass(module.status)">
                            {{ module.status }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useActivity } from '../composables/MC_useActivity';
import { useModules } from '../composables/MC_useModules';
import { useToast } from '../composables/MC_useToast';

const { t: __ } = useI18n();
const toast = useToast();
const { modules, fetchModules } = useModules();
const { activity, fetchActivity } = useActivity();

const isLoading = ref(false);
const stats = ref({
    activeModules: 0,
    totalModules: 0,
    recentOrders: 0,
    systemStatus: 'Operational',
    systemMessage: '',
    cacheHitRate: 0,
});

// Format date for display
const formatDate = (timestamp) => {
    return new Date(timestamp).toLocaleString();
};

// Get badge class for event type
const getEventBadgeClass = (type) => {
    switch (type) {
        case 'error':
            return 'badge-error';
        case 'warning':
            return 'badge-warning';
        case 'success':
            return 'badge-success';
        default:
            return 'badge-info';
    }
};

// Get badge class for module status
const getModuleBadgeClass = (status) => {
    switch (status) {
        case 'active':
            return 'badge-success';
        case 'inactive':
            return 'badge-warning';
        case 'error':
            return 'badge-error';
        default:
            return 'badge-ghost';
    }
};

// Fetch dashboard stats from the REST API
const fetchStats = async () => {
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/dashboard/stats');
        if (!response.ok) {
            throw new Error('Failed to fetch dashboard stats');
        }
        const data = await response.json();
        stats.value = data;
    } catch (error) {
        console.error('Error fetching dashboard stats:', error);
        toast.error('Failed to fetch dashboard stats: ' + error.message);
    }
};

// Refresh all dashboard data
const refreshData = async () => {
    isLoading.value = true;
    try {
        await Promise.all([fetchStats(), fetchModules(), fetchActivity()]);
    } catch (error) {
        console.error('Error refreshing dashboard data:', error);
        toast.error('Failed to refresh dashboard data: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Initialize
onMounted(() => {
    refreshData();
});
</script>

<style scoped>
.mc-dashboard {
    @apply p-6;
}

.stat {
    @apply p-6 transition-all duration-200;
}

.stat:hover {
    @apply shadow-2xl;
}

.stat-title {
    @apply text-base-content opacity-70;
}

.stat-value {
    @apply text-4xl font-bold;
}

.stat-desc {
    @apply text-base-content opacity-60;
}

.card {
    @apply transition-all duration-200;
}

.card:hover {
    @apply shadow-2xl;
}
</style>
