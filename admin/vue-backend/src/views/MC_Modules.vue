<!-- MC_Modules.vue - Module management component for Mesmeric Commerce -->
<template>
    <div class="mc-modules">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Modules') }}</h1>
            <button class="btn btn-primary" :disabled="isLoading" @click="refreshModules">
                <span v-if="!isLoading">{{ __('Refresh') }}</span>
                <span v-else>{{ __('Loading...') }}</span>
            </button>
        </div>

        <!-- Module Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="module in modules" :key="module.id" class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title flex justify-between">
                        {{ module.name }}
                        <div class="badge" :class="getStatusBadgeClass(module.status)">
                            {{ module.status }}
                        </div>
                    </h2>
                    <p>{{ module.description }}</p>
                    <div class="card-actions justify-end mt-4">
                        <button
                            class="btn"
                            :class="module.status === 'active' ? 'btn-error' : 'btn-success'"
                            :disabled="isLoading"
                            @click="toggleModule(module)"
                        >
                            {{ module.status === 'active' ? __('Deactivate') : __('Activate') }}
                        </button>
                        <button
                            class="btn btn-ghost"
                            :disabled="!module.hasSettings || isLoading"
                            @click="openSettings(module)"
                        >
                            {{ __('Settings') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <dialog ref="settingsModal" class="modal" :class="{ 'modal-open': selectedModule }">
            <div class="modal-box">
                <h3 class="font-bold text-lg mb-4">
                    {{ selectedModule ? `${selectedModule.name} ${__('Settings')}` : '' }}
                </h3>
                <div v-if="selectedModule" class="space-y-4">
                    <component
                        :is="selectedModule.settingsComponent"
                        :module="selectedModule"
                        @save="saveSettings"
                        @cancel="closeSettings"
                    />
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button @click="closeSettings">{{ __('Close') }}</button>
            </form>
        </dialog>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '../composables/MC_useToast';

const { t: __ } = useI18n();
const toast = useToast();

const modules = ref([]);
const isLoading = ref(false);
const selectedModule = ref(null);
const settingsModal = ref(null);

// Fetch modules from the REST API
const fetchModules = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/modules');
        if (!response.ok) {
            throw new Error('Failed to fetch modules');
        }
        const data = await response.json();
        modules.value = data;
    } catch (error) {
        console.error('Error fetching modules:', error);
        toast.error('Failed to fetch modules: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Refresh modules list
const refreshModules = () => {
    fetchModules();
};

// Toggle module activation status
const toggleModule = async (module) => {
    isLoading.value = true;
    try {
        const response = await fetch(`/wp-json/mesmeric-commerce/v1/modules/${module.id}/toggle`, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': window.mesCommerce.nonce,
            },
        });
        if (!response.ok) {
            throw new Error('Failed to toggle module');
        }
        const updatedModule = await response.json();
        const index = modules.value.findIndex((m) => m.id === module.id);
        if (index !== -1) {
            modules.value[index] = updatedModule;
        }
        toast.success(
            `Module ${updatedModule.status === 'active' ? 'activated' : 'deactivated'} successfully`
        );
    } catch (error) {
        console.error('Error toggling module:', error);
        toast.error('Failed to toggle module: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Open module settings
const openSettings = (module) => {
    selectedModule.value = module;
    settingsModal.value?.showModal();
};

// Close module settings
const closeSettings = () => {
    selectedModule.value = null;
    settingsModal.value?.close();
};

// Save module settings
const saveSettings = async (settings) => {
    if (!selectedModule.value) return;

    isLoading.value = true;
    try {
        const response = await fetch(
            `/wp-json/mesmeric-commerce/v1/modules/${selectedModule.value.id}/settings`,
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
        closeSettings();
        await fetchModules(); // Refresh modules to get updated settings
    } catch (error) {
        console.error('Error saving settings:', error);
        toast.error('Failed to save settings: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Get badge class based on module status
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

// Initialize
onMounted(() => {
    fetchModules();
});
</script>

<style scoped>
.mc-modules {
    @apply p-6;
}

.card {
    @apply transition-all duration-200;
}

.card:hover {
    @apply shadow-2xl;
}

.modal-box {
    @apply max-w-2xl;
}
</style>
