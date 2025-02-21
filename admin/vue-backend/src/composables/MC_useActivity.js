import { ref } from 'vue';
import { useToast } from './MC_useToast';

const activity = ref([]);
const isLoading = ref(false);

export const useActivity = () => {
    const toast = useToast();

    // Fetch activity log
    const fetchActivity = async () => {
        isLoading.value = true;
        try {
            const response = await fetch('/wp-json/mesmeric-commerce/v1/activity');
            if (!response.ok) {
                throw new Error('Failed to fetch activity log');
            }
            const data = await response.json();
            activity.value = data;
        } catch (error) {
            console.error('Error fetching activity log:', error);
            toast.error('Failed to fetch activity log: ' + error.message);
        } finally {
            isLoading.value = false;
        }
    };

    // Clear activity log
    const clearActivity = async () => {
        try {
            const response = await fetch('/wp-json/mesmeric-commerce/v1/activity', {
                method: 'DELETE',
                headers: {
                    'X-WP-Nonce': window.mesCommerce.nonce,
                },
            });
            if (!response.ok) {
                throw new Error('Failed to clear activity log');
            }
            activity.value = [];
            toast.success('Activity log cleared successfully');
        } catch (error) {
            console.error('Error clearing activity log:', error);
            toast.error('Failed to clear activity log: ' + error.message);
            throw error;
        }
    };

    // Filter activity by type
    const filterByType = (type) => {
        return activity.value.filter((event) => event.type === type);
    };

    // Filter activity by module
    const filterByModule = (moduleId) => {
        return activity.value.filter((event) => event.moduleId === moduleId);
    };

    // Filter activity by date range
    const filterByDateRange = (startDate, endDate) => {
        return activity.value.filter((event) => {
            const eventDate = new Date(event.timestamp);
            return eventDate >= startDate && eventDate <= endDate;
        });
    };

    return {
        activity,
        isLoading,
        fetchActivity,
        clearActivity,
        filterByType,
        filterByModule,
        filterByDateRange,
    };
};
