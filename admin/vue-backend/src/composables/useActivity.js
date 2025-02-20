import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from './useToast'

export function useActivity() {
    const { t: __ } = useI18n()
    const { addToast } = useToast()
    const recentActivity = ref([])
    const loading = ref(false)

    const fetchActivity = async () => {
        loading.value = true
        try {
            const response = await fetch(`${window.mcAdminData.ajaxUrl}?action=mc_get_recent_activity`, {
                headers: {
                    'X-WP-Nonce': window.mcAdminData.nonce
                }
            })
            const data = await response.json()

            if (data.success) {
                recentActivity.value = data.data
            }
        } catch (error) {
            console.error('Error fetching activity:', error)
            addToast({
                message: __('Error loading recent activity'),
                type: 'error'
            })
        } finally {
            loading.value = false
        }
    }

    const addActivity = async (activity) => {
        try {
            const response = await fetch(`${window.mcAdminData.ajaxUrl}?action=mc_add_activity`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.mcAdminData.nonce
                },
                body: JSON.stringify(activity)
            })
            const data = await response.json()

            if (data.success) {
                recentActivity.value.unshift(data.data)
                // Keep only the last 50 activities
                if (recentActivity.value.length > 50) {
                    recentActivity.value.pop()
                }
            }
        } catch (error) {
            console.error('Error adding activity:', error)
            addToast({
                message: __('Error recording activity'),
                type: 'error'
            })
        }
    }

    onMounted(() => {
        fetchActivity()
    })

    return {
        recentActivity,
        loading,
        fetchActivity,
        addActivity
    }
}
