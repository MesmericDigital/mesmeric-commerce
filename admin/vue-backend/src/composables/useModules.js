import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from './useToast'

export function useModules() {
    const { t: __ } = useI18n()
    const { addToast } = useToast()
    const modules = ref([])
    const loading = ref(false)

    const fetchModules = async () => {
        loading.value = true
        try {
            const response = await fetch(`${window.mcAdminData.ajaxUrl}?action=mc_get_modules`, {
                headers: {
                    'X-WP-Nonce': window.mcAdminData.nonce
                }
            })
            const data = await response.json()

            if (data.success) {
                modules.value = data.data.map(module => ({
                    ...module,
                    loading: false
                }))
            }
        } catch (error) {
            console.error('Error fetching modules:', error)
            addToast({
                message: __('Error loading modules'),
                type: 'error'
            })
        } finally {
            loading.value = false
        }
    }

    const toggleModule = async (module) => {
        module.loading = true
        try {
            const response = await fetch(`${window.mcAdminData.ajaxUrl}?action=mc_update_module_status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.mcAdminData.nonce
                },
                body: JSON.stringify({
                    module: module.id,
                    enabled: !module.enabled
                })
            })
            const data = await response.json()

            if (data.success) {
                module.enabled = !module.enabled
                addToast({
                    message: __('Module status updated successfully'),
                    type: 'success'
                })
            }
        } catch (error) {
            console.error('Error toggling module:', error)
            addToast({
                message: __('Error updating module status'),
                type: 'error'
            })
        } finally {
            module.loading = false
        }
    }

    onMounted(() => {
        fetchModules()
    })

    return {
        modules,
        loading,
        fetchModules,
        toggleModule
    }
}
