import { ref } from 'vue';

// Toast state
const toasts = ref([]);
let nextId = 1;

// Toast types and their default durations
const TOAST_DURATIONS = {
    success: 3000,
    error: 5000,
    warning: 4000,
    info: 3000,
};

// Create a new toast
const addToast = ({ message, type = 'info', duration }) => {
    const id = nextId++;
    const toast = {
        id,
        message,
        type,
    };

    toasts.value.push(toast);

    // Auto dismiss after duration
    setTimeout(() => {
        dismissToast(id);
    }, duration || TOAST_DURATIONS[type] || TOAST_DURATIONS.info);
};

// Remove a toast by ID
const dismissToast = (id) => {
    const index = toasts.value.findIndex((t) => t.id === id);
    if (index !== -1) {
        toasts.value.splice(index, 1);
    }
};

// Convenience methods for different toast types
const success = (message, duration) => addToast({ message, type: 'success', duration });
const error = (message, duration) => addToast({ message, type: 'error', duration });
const warning = (message, duration) => addToast({ message, type: 'warning', duration });
const info = (message, duration) => addToast({ message, type: 'info', duration });

// Export the composable
export const useToast = () => {
    return {
        toasts,
        addToast,
        dismissToast,
        success,
        error,
        warning,
        info,
    };
};
