<?php
<template>
  <div class="logs-view">
    <!-- Filters -->
    <div class="filters bg-base-200 p-4 rounded-lg mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text">{{ __('Log Type') }}</span>
          </label>
          <select v-model="filters.type" class="select select-bordered w-full">
            <option value="">{{ __('All Types') }}</option>
            <option value="woocommerce_order">{{ __('Orders') }}</option>
            <option value="woocommerce_inventory">{{ __('Inventory') }}</option>
            <option value="woocommerce_shipping">{{ __('Shipping') }}</option>
            <option value="woocommerce_user">{{ __('User Activity') }}</option>
          </select>
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">{{ __('Date Range') }}</span>
          </label>
          <select v-model="filters.dateRange" class="select select-bordered w-full">
            <option value="today">{{ __('Today') }}</option>
            <option value="yesterday">{{ __('Yesterday') }}</option>
            <option value="last7days">{{ __('Last 7 Days') }}</option>
            <option value="last30days">{{ __('Last 30 Days') }}</option>
            <option value="custom">{{ __('Custom Range') }}</option>
          </select>
        </div>

        <div v-if="filters.dateRange === 'custom'" class="form-control">
          <label class="label">
            <span class="label-text">{{ __('Start Date') }}</span>
          </label>
          <input
            type="date"
            v-model="filters.startDate"
            class="input input-bordered w-full"
          />
        </div>

        <div v-if="filters.dateRange === 'custom'" class="form-control">
          <label class="label">
            <span class="label-text">{{ __('End Date') }}</span>
          </label>
          <input
            type="date"
            v-model="filters.endDate"
            class="input input-bordered w-full"
          />
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">{{ __('Search') }}</span>
          </label>
          <input
            type="text"
            v-model="filters.search"
            :placeholder="__('Search logs...')"
            class="input input-bordered w-full"
          />
        </div>
      </div>
    </div>

    <!-- Log Table -->
    <div class="overflow-x-auto bg-base-100 rounded-lg shadow">
      <table class="table table-zebra w-full">
        <thead>
          <tr>
            <th>{{ __('Timestamp') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Message') }}</th>
            <th>{{ __('Details') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in filteredLogs" :key="log.id">
            <td class="whitespace-nowrap">{{ formatDate(log.timestamp) }}</td>
            <td>
              <span
                class="badge"
                :class="{
                  'badge-info': log.context === 'woocommerce_order',
                  'badge-warning': log.context === 'woocommerce_inventory',
                  'badge-success': log.context === 'woocommerce_shipping',
                  'badge-neutral': log.context === 'woocommerce_user'
                }"
              >
                {{ formatLogType(log.context) }}
              </span>
            </td>
            <td>{{ log.message }}</td>
            <td>
              <button
                class="btn btn-ghost btn-xs"
                @click="showLogDetails(log)"
              >
                {{ __('View') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="flex justify-between items-center p-4">
        <div class="text-sm text-base-content/70">
          {{ __('Showing') }} {{ paginationStart }} {{ __('to') }} {{ paginationEnd }} {{ __('of') }} {{ totalLogs }} {{ __('entries') }}
        </div>
        <div class="join">
          <button
            class="join-item btn"
            :class="{ 'btn-disabled': currentPage === 1 }"
            @click="currentPage--"
          >
            «
          </button>
          <button class="join-item btn">{{ currentPage }}</button>
          <button
            class="join-item btn"
            :class="{ 'btn-disabled': currentPage === totalPages }"
            @click="currentPage++"
          >
            »
          </button>
        </div>
      </div>
    </div>

    <!-- Log Details Modal -->
    <dialog ref="logModal" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ __('Log Details') }}</h3>
        <pre v-if="selectedLog" class="bg-base-200 p-4 rounded-lg overflow-x-auto">
          <code>{{ JSON.stringify(selectedLog.data, null, 2) }}</code>
        </pre>
        <div class="modal-action">
          <form method="dialog">
            <button class="btn">{{ __('Close') }}</button>
          </form>
        </div>
      </div>
    </dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { format } from 'date-fns';

const { t: __ } = useI18n();

// State
const logs = ref([]);
const currentPage = ref(1);
const itemsPerPage = 20;
const selectedLog = ref(null);
const logModal = ref(null);

const filters = ref({
    type: '',
    dateRange: 'last7days',
    startDate: '',
    endDate: '',
    search: '',
});

// Computed
const filteredLogs = computed(() => {
    let filtered = [...logs.value];

    // Apply type filter
    if (filters.value.type) {
        filtered = filtered.filter((log) => log.context === filters.value.type);
    }

    // Apply date filter
    const today = new Date();
    let startDate = new Date();
    let endDate = new Date();

    switch (filters.value.dateRange) {
        case 'today':
            startDate.setHours(0, 0, 0, 0);
            break;
        case 'yesterday':
            startDate.setDate(today.getDate() - 1);
            startDate.setHours(0, 0, 0, 0);
            endDate.setDate(today.getDate() - 1);
            endDate.setHours(23, 59, 59, 999);
            break;
        case 'last7days':
            startDate.setDate(today.getDate() - 7);
            break;
        case 'last30days':
            startDate.setDate(today.getDate() - 30);
            break;
        case 'custom':
            if (filters.value.startDate && filters.value.endDate) {
                startDate = new Date(filters.value.startDate);
                endDate = new Date(filters.value.endDate);
            }
            break;
    }

    filtered = filtered.filter((log) => {
        const logDate = new Date(log.timestamp);
        return logDate >= startDate && logDate <= endDate;
    });

    // Apply search filter
    if (filters.value.search) {
        const searchTerm = filters.value.search.toLowerCase();
        filtered = filtered.filter(
            (log) =>
                log.message.toLowerCase().includes(searchTerm) ||
                JSON.stringify(log.data).toLowerCase().includes(searchTerm)
        );
    }

    return filtered;
});

const paginatedLogs = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredLogs.value.slice(start, start + itemsPerPage);
});

const totalLogs = computed(() => filteredLogs.value.length);
const totalPages = computed(() => Math.ceil(totalLogs.value / itemsPerPage));
const paginationStart = computed(() => (currentPage.value - 1) * itemsPerPage + 1);
const paginationEnd = computed(() => Math.min(currentPage.value * itemsPerPage, totalLogs.value));

// Methods
const formatDate = (date) => {
    return format(new Date(date), 'yyyy-MM-dd HH:mm:ss');
};

const formatLogType = (context) => {
    return context.replace('woocommerce_', '').replace('_', ' ').toUpperCase();
};

const showLogDetails = (log) => {
    selectedLog.value = log;
    logModal.value.showModal();
};

const fetchLogs = async () => {
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/logs', {
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': window.mcAdminData.nonce,
            },
        });

        if (!response.ok) {
            throw new Error('Failed to fetch logs');
        }

        logs.value = await response.json();
    } catch (error) {
        console.error('Error fetching logs:', error);
        // Show error toast
    }
};

// Lifecycle
onMounted(() => {
    fetchLogs();
});
</script>
