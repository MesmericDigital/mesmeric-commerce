<!-- MC_Logs.vue - Log viewer component for Mesmeric Commerce -->
<template>
    <div class="mc-logs-container">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">System Logs</h1>
            <div class="flex gap-4">
                <button class="btn btn-primary" :disabled="isLoading" @click="refreshLogs">
                    <span v-if="!isLoading">Refresh</span>
                    <span v-else>Loading...</span>
                </button>
                <button
                    class="btn btn-error"
                    :disabled="isLoading || !logs.length"
                    @click="clearLogs"
                >
                    Clear Logs
                </button>
            </div>
        </div>

        <!-- Log Filters -->
        <div class="mb-6 flex gap-4">
            <select v-model="selectedLevel" class="select select-bordered w-full max-w-xs">
                <option value="">All Levels</option>
                <option value="error">Error</option>
                <option value="warning">Warning</option>
                <option value="info">Info</option>
                <option value="debug">Debug</option>
            </select>

            <input
                v-model="searchQuery"
                type="text"
                placeholder="Search logs..."
                class="input input-bordered w-full"
            />
        </div>

        <!-- Log Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Level</th>
                        <th>Message</th>
                        <th>Context</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading">
                        <td colspan="4" class="text-center py-4">Loading logs...</td>
                    </tr>
                    <tr v-else-if="!filteredLogs.length">
                        <td colspan="4" class="text-center py-4">No logs found.</td>
                    </tr>
                    <tr
                        v-for="log in filteredLogs"
                        :key="log.id"
                        :class="{
                            'bg-red-50': log.level === 'error',
                            'bg-yellow-50': log.level === 'warning',
                            'bg-blue-50': log.level === 'info',
                            'bg-gray-50': log.level === 'debug',
                        }"
                    >
                        <td class="whitespace-nowrap">{{ formatDate(log.timestamp) }}</td>
                        <td>
                            <span
                                class="px-2 py-1 rounded text-sm font-medium"
                                :class="{
                                    'bg-red-100 text-red-800': log.level === 'error',
                                    'bg-yellow-100 text-yellow-800': log.level === 'warning',
                                    'bg-blue-100 text-blue-800': log.level === 'info',
                                    'bg-gray-100 text-gray-800': log.level === 'debug',
                                }"
                            >
                                {{ log.level }}
                            </span>
                        </td>
                        <td class="whitespace-pre-wrap">{{ log.message }}</td>
                        <td class="whitespace-pre-wrap">
                            <pre v-if="log.context" class="text-sm bg-gray-50 p-2 rounded">{{
                                JSON.stringify(log.context, null, 2)
                            }}</pre>
                            <span v-else>-</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useToast } from '../composables/MC_useToast';

const toast = useToast();
const logs = ref([]);
const isLoading = ref(false);
const selectedLevel = ref('');
const searchQuery = ref('');

// Computed property for filtered logs
const filteredLogs = computed(() => {
    return logs.value
        .filter((log) => {
            if (selectedLevel.value && log.level !== selectedLevel.value) {
                return false;
            }
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                return (
                    log.message.toLowerCase().includes(query) ||
                    log.level.toLowerCase().includes(query) ||
                    (log.context && JSON.stringify(log.context).toLowerCase().includes(query))
                );
            }
            return true;
        })
        .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
});

// Format date for display
const formatDate = (timestamp) => {
    return new Date(timestamp).toLocaleString();
};

// Fetch logs from the REST API
const fetchLogs = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/logs');
        if (!response.ok) {
            throw new Error('Failed to fetch logs');
        }
        const data = await response.json();
        logs.value = data;
    } catch (error) {
        console.error('Error fetching logs:', error);
        toast.error('Failed to fetch logs: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Refresh logs
const refreshLogs = () => {
    fetchLogs();
};

// Clear all logs
const clearLogs = async () => {
    if (!confirm('Are you sure you want to clear all logs?')) {
        return;
    }

    isLoading.value = true;
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/logs', {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': window.mesCommerce.nonce,
            },
        });
        if (!response.ok) {
            throw new Error('Failed to clear logs');
        }
        logs.value = [];
        toast.success('Logs cleared successfully');
    } catch (error) {
        console.error('Error clearing logs:', error);
        toast.error('Failed to clear logs: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Fetch logs on component mount
onMounted(() => {
    fetchLogs();
});
</script>

<style scoped>
.mc-logs-container {
    @apply p-6;
}

/* Ensure table header stays fixed */
.table thead tr {
    @apply bg-gray-50;
}

/* Add some hover effect to table rows */
.table tbody tr:hover {
    @apply bg-gray-50;
}

/* Style the log level badges */
.level-badge {
    @apply px-2 py-1 rounded text-sm font-medium;
}
</style>
