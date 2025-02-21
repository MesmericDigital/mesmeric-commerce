import { ref } from 'vue';
import { useToast } from './MC_useToast';

const modules = ref([]);
const isLoading = ref(false);

export const useModules = () => {
    const toast = useToast();

    // Fetch all modules
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

    // Get a single module by ID
    const getModule = (id) => {
        return modules.value.find((m) => m.id === id);
    };

    // Toggle module status
    const toggleModule = async (moduleId) => {
        try {
            const response = await fetch(`/wp-json/mesmeric-commerce/v1/modules/${moduleId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': window.mesCommerce.nonce,
                },
            });
            if (!response.ok) {
                throw new Error('Failed to toggle module');
            }
            const updatedModule = await response.json();
            const index = modules.value.findIndex((m) => m.id === moduleId);
            if (index !== -1) {
                modules.value[index] = updatedModule;
            }
            toast.success(
                `Module ${updatedModule.status === 'active' ? 'activated' : 'deactivated'} successfully`
            );
            return updatedModule;
        } catch (error) {
            console.error('Error toggling module:', error);
            toast.error('Failed to toggle module: ' + error.message);
            throw error;
        }
    };

    // Update module settings
    const updateModuleSettings = async (moduleId, settings) => {
        try {
            const response = await fetch(`/wp-json/mesmeric-commerce/v1/modules/${moduleId}/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.mesCommerce.nonce,
                },
                body: JSON.stringify(settings),
            });
            if (!response.ok) {
                throw new Error('Failed to update module settings');
            }
            const updatedModule = await response.json();
            const index = modules.value.findIndex((m) => m.id === moduleId);
            if (index !== -1) {
                modules.value[index] = updatedModule;
            }
            toast.success('Module settings updated successfully');
            return updatedModule;
        } catch (error) {
            console.error('Error updating module settings:', error);
            toast.error('Failed to update module settings: ' + error.message);
            throw error;
        }
    };

    return {
        modules,
        isLoading,
        fetchModules,
        getModule,
        toggleModule,
        updateModuleSettings,
    };
};
