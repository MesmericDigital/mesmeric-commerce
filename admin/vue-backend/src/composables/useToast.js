import { ref } from 'vue'

export function useToast() {
    const toasts = ref([])
    let nextId = 0

    const addToast = ({ message, type = 'info', timeout = 3000 }) => {
        const id = nextId++
        toasts.value.push({ id, message, type })

        if (timeout) {
            setTimeout(() => {
                dismissToast(id)
            }, timeout)
        }
    }

    const dismissToast = (id) => {
        const index = toasts.value.findIndex(toast => toast.id === id)
        if (index > -1) {
            toasts.value.splice(index, 1)
        }
    }

    return {
        toasts,
        addToast,
        dismissToast
    }
}
