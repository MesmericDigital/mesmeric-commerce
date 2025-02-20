<?php
<template>
  <div class="dashboard">
    <!-- Quick Stats -->
    <div class="stats shadow mb-8 w-full">
      <div class="stat" v-for="stat in stats" :key="stat.id">
        <div class="stat-title">{{ stat.label }}</div>
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-desc">{{ stat.description }}</div>
      </div>
    </div>

    <!-- Module Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <div v-for="module in modules"
           :key="module.id"
           class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">
            {{ module.name }}
            <div :class="['badge', module.enabled ? 'badge-success' : 'badge-ghost']">
              {{ module.enabled ? __('Active') : __('Inactive') }}
            </div>
          </h2>
          <p>{{ module.description }}</p>
          <div class="card-actions justify-end">
            <button class="btn btn-primary btn-sm"
                    :class="{ 'loading': module.loading }"
                    @click="toggleModule(module)">
              {{ module.enabled ? __('Disable') : __('Enable') }}
            </button>
            <router-link :to="`/modules/${module.id}`"
                        class="btn btn-ghost btn-sm">
              {{ __('Manage') }}
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title mb-4">{{ __('Recent Activity') }}</h2>
        <div class="overflow-x-auto">
          <table class="table table-zebra w-full">
            <thead>
              <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Event') }}</th>
                <th>{{ __('Details') }}</th>
                <th>{{ __('User') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="activity in recentActivity" :key="activity.id">
                <td>{{ formatDate(activity.date) }}</td>
                <td>{{ activity.event }}</td>
                <td>{{ activity.details }}</td>
                <td>{{ activity.user }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';
import { useModules } from '@/composables/useModules';
import { useActivity } from '@/composables/useActivity';

const { t: __ } = useI18n();
const { addToast } = useToast();
const { modules, toggleModule } = useModules();
const { recentActivity } = useActivity();

// Quick stats data
const stats = ref([
    {
        id: 'quick_views',
        label: __('Quick Views'),
        value: '0',
        description: __('Last 24 hours'),
    },
    {
        id: 'wishlists',
        label: __('Wishlists'),
        value: '0',
        description: __('Total active'),
    },
    {
        id: 'low_stock',
        label: __('Low Stock'),
        value: '0',
        description: __('Items to reorder'),
    },
    {
        id: 'faqs',
        label: __('FAQs'),
        value: '0',
        description: __('Total published'),
    },
]);

// Format date helper
const formatDate = (date) => {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

// Load initial data
onMounted(async () => {
    try {
        const response = await fetch(
            `${window.mcAdminData.ajaxUrl}?action=mc_get_dashboard_stats`,
            {
                headers: {
                    'X-WP-Nonce': window.mcAdminData.nonce,
                },
            }
        );
        const data = await response.json();

        if (data.success) {
            stats.value = stats.value.map((stat) => ({
                ...stat,
                value: data.data[stat.id] || '0',
            }));
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        addToast({
            message: __('Error loading dashboard statistics'),
            type: 'error',
        });
    }
});
</script>

<style scoped>
.dashboard {
    @apply space-y-8;
}

.stat-value {
    @apply text-4xl font-bold;
}
</style>
