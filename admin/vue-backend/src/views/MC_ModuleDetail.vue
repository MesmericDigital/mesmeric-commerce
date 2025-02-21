<!-- MC_ModuleDetail.vue - Module detail view component for Mesmeric Commerce -->
<template>
    <div class="mc-module-detail">
        <div v-if="isLoading" class="flex justify-center items-center min-h-[400px]">
            <div class="loading loading-spinner loading-lg"></div>
        </div>

        <div v-else-if="!module" class="text-center py-8">
            <h2 class="text-xl font-bold text-gray-800 mb-2">{{ __('Module Not Found') }}</h2>
            <p class="text-gray-600">{{ __('The requested module could not be found.') }}</p>
            <router-link to="/modules" class="btn btn-primary mt-4">
                {{ __('Back to Modules') }}
            </router-link>
        </div>

        <div v-else class="space-y-8">
            <!-- Module Header -->
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ module.name }}</h1>
                    <p class="text-gray-600">{{ module.description }}</p>
                </div>
                <div class="flex gap-4">
                    <button
                        class="btn"
                        :class="module.status === 'active' ? 'btn-error' : 'btn-success'"
                        :disabled="isUpdating"
                        @click="toggleModule"
                    >
                        {{ module.status === 'active' ? __('Deactivate') : __('Activate') }}
                    </button>
                </div>
            </div>

            <!-- Module Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status Card -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ __('Status Information') }}</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Status') }}</span>
                                <span class="badge" :class="getStatusBadgeClass(module.status)">
                                    {{ module.status }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Version') }}</span>
                                <span>{{ module.version }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">{{ __('Last Updated') }}</span>
                                <span>{{ formatDate(module.lastUpdated) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dependencies Card -->
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body">
                        <h2 class="card-title">{{ __('Dependencies') }}</h2>
                        <div v-if="!module.dependencies?.length" class="text-gray-600">
                            {{ __('No dependencies required') }}
                        </div>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="dep in module.dependencies"
                                :key="dep.name"
                                class="flex justify-between items-center"
                            >
                                <span>{{ dep.name }}</span>
                                <span
                                    class="badge"
                                    :class="dep.isInstalled ? 'badge-success' : 'badge-error'"
                                >
                                    {{ dep.isInstalled ? __('Installed') : __('Missing') }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div v-if="module.hasSettings" class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title mb-4">{{ __('Module Settings') }}</h2>
                    <component
                        :is="module.settingsComponent"
                        :module="module"
                        @save="saveSettings"
                    />
                </div>
            </div>

            <!-- Documentation -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body prose max-w-none">
                    <h2 class="card-title">{{ __('Documentation') }}</h2>
                    <div v-html="module.documentation"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { useToast } from '../composables/MC_useToast';

const { t: __ } = useI18n();
const route = useRoute();
const toast = useToast();

const module = ref(null);
const isLoading = ref(true);
const isUpdating = ref(false);

// Format date for display
const formatDate = (timestamp) => {
    return new Date(timestamp).toLocaleString();
};

// Get badge class for module status
const getStatusBadgeClass = (status) => {
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

// Fetch module details from the REST API
const fetchModule = async () => {
    isLoading.value = true;
    try {
        const response = await fetch(`/wp-json/mesmeric-commerce/v1/modules/${route.params.id}`);
        if (!response.ok) {
            throw new Error('Failed to fetch module details');
        }
        const data = await response.json();
        module.value = data;
    } catch (error) {
        console.error('Error fetching module details:', error);
        toast.error('Failed to fetch module details: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Toggle module activation status
const toggleModule = async () => {
    isUpdating.value = true;
    try {
        const response = await fetch(
            `/wp-json/mesmeric-commerce/v1/modules/${module.value.id}/toggle`,
            {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': window.mesCommerce.nonce,
                },
            }
        );
        if (!response.ok) {
            throw new Error('Failed to toggle module');
        }
        const updatedModule = await response.json();
        module.value = updatedModule;
        toast.success(
            `Module ${updatedModule.status === 'active' ? 'activated' : 'deactivated'} successfully`
        );
    } catch (error) {
        console.error('Error toggling module:', error);
        toast.error('Failed to toggle module: ' + error.message);
    } finally {
        isUpdating.value = false;
    }
};

// Save module settings
const saveSettings = async (settings) => {
    isUpdating.value = true;
    try {
        const response = await fetch(
            `/wp-json/mesmeric-commerce/v1/modules/${module.value.id}/settings`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.mesCommerce.nonce,
                },
                body: JSON.stringify(settings),
            }
        );
        if (!response.ok) {
            throw new Error('Failed to save settings');
        }
        toast.success('Settings saved successfully');
        await fetchModule(); // Refresh module data
    } catch (error) {
        console.error('Error saving settings:', error);
        toast.error('Failed to save settings: ' + error.message);
    } finally {
        isUpdating.value = false;
    }
};

// Initialize
onMounted(() => {
    fetchModule();
});
</script>

<style scoped>
.mc-module-detail {
    @apply p-6;
}

.card {
    @apply transition-all duration-200;
}

.card:hover {
    @apply shadow-2xl;
}

/* Style markdown content in documentation */
:deep(.prose) {
    @apply text-base-content;
}

:deep(.prose h1),
:deep(.prose h2),
:deep(.prose h3) {
    @apply text-base-content;
}

:deep(.prose a) {
    @apply text-primary hover:text-primary-focus;
}

:deep(.prose code) {
    @apply bg-base-200 px-2 py-1 rounded;
}
</style>
