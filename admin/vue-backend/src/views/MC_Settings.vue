<!-- MC_Settings.vue - Global settings component for Mesmeric Commerce -->
<template>
    <div class="mc-settings">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">{{ __('Settings') }}</h1>
            <div class="flex gap-4">
                <button
                    class="btn btn-primary"
                    :disabled="isLoading || !hasChanges"
                    @click="saveSettings"
                >
                    <span v-if="!isLoading">{{ __('Save Changes') }}</span>
                    <span v-else>{{ __('Saving...') }}</span>
                </button>
                <button
                    class="btn btn-ghost"
                    :disabled="isLoading || !hasChanges"
                    @click="resetSettings"
                >
                    {{ __('Reset') }}
                </button>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="space-y-8">
            <!-- General Settings -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">{{ __('General Settings') }}</h2>

                    <!-- Debug Mode -->
                    <div class="form-control">
                        <label class="label cursor-pointer">
                            <span class="label-text">{{ __('Debug Mode') }}</span>
                            <input
                                v-model="settings.debug_mode"
                                type="checkbox"
                                class="toggle toggle-primary"
                                @change="markAsChanged"
                            />
                        </label>
                        <span class="text-sm text-gray-500">
                            {{ __('Enable detailed logging and debugging information') }}
                        </span>
                    </div>

                    <!-- Cache Duration -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ __('Cache Duration (minutes)') }}</span>
                        </label>
                        <input
                            v-model.number="settings.cache_duration"
                            type="number"
                            class="input input-bordered w-full max-w-xs"
                            min="0"
                            @input="markAsChanged"
                        />
                        <span class="text-sm text-gray-500">
                            {{ __('Set to 0 to disable caching') }}
                        </span>
                    </div>

                    <!-- API Keys -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ __('API Key') }}</span>
                        </label>
                        <div class="join">
                            <input
                                v-model="settings.api_key"
                                :type="showApiKey ? 'text' : 'password'"
                                class="input input-bordered join-item flex-1"
                                @input="markAsChanged"
                            />
                            <button class="btn join-item" @click="showApiKey = !showApiKey">
                                {{ showApiKey ? __('Hide') : __('Show') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Settings -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">{{ __('Email Settings') }}</h2>

                    <!-- Email Notifications -->
                    <div class="form-control">
                        <label class="label cursor-pointer">
                            <span class="label-text">{{ __('Enable Email Notifications') }}</span>
                            <input
                                v-model="settings.email_notifications"
                                type="checkbox"
                                class="toggle toggle-primary"
                                @change="markAsChanged"
                            />
                        </label>
                    </div>

                    <!-- Admin Email -->
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">{{ __('Admin Email') }}</span>
                        </label>
                        <input
                            v-model="settings.admin_email"
                            type="email"
                            class="input input-bordered w-full"
                            @input="markAsChanged"
                        />
                    </div>
                </div>
            </div>

            <!-- Performance Settings -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title">{{ __('Performance Settings') }}</h2>

                    <!-- Asset Minification -->
                    <div class="form-control">
                        <label class="label cursor-pointer">
                            <span class="label-text">{{ __('Minify Assets') }}</span>
                            <input
                                v-model="settings.minify_assets"
                                type="checkbox"
                                class="toggle toggle-primary"
                                @change="markAsChanged"
                            />
                        </label>
                        <span class="text-sm text-gray-500">
                            {{ __('Minify CSS and JavaScript files') }}
                        </span>
                    </div>

                    <!-- Image Optimization -->
                    <div class="form-control">
                        <label class="label cursor-pointer">
                            <span class="label-text">{{ __('Optimize Images') }}</span>
                            <input
                                v-model="settings.optimize_images"
                                type="checkbox"
                                class="toggle toggle-primary"
                                @change="markAsChanged"
                            />
                        </label>
                        <span class="text-sm text-gray-500">
                            {{ __('Automatically optimize uploaded images') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToast } from '../composables/MC_useToast';

const { t: __ } = useI18n();
const toast = useToast();

const isLoading = ref(false);
const hasChanges = ref(false);
const showApiKey = ref(false);

// Settings state
const settings = ref({
    debug_mode: false,
    cache_duration: 60,
    api_key: '',
    email_notifications: true,
    admin_email: '',
    minify_assets: true,
    optimize_images: true,
});

// Original settings for comparison
const originalSettings = ref(null);

// Mark settings as changed
const markAsChanged = () => {
    if (!originalSettings.value) return;

    hasChanges.value = Object.keys(settings.value).some(
        (key) => settings.value[key] !== originalSettings.value[key]
    );
};

// Fetch settings from the REST API
const fetchSettings = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/settings');
        if (!response.ok) {
            throw new Error('Failed to fetch settings');
        }
        const data = await response.json();
        settings.value = data;
        originalSettings.value = { ...data };
        hasChanges.value = false;
    } catch (error) {
        console.error('Error fetching settings:', error);
        toast.error('Failed to fetch settings: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Save settings
const saveSettings = async () => {
    isLoading.value = true;
    try {
        const response = await fetch('/wp-json/mesmeric-commerce/v1/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.mesCommerce.nonce,
            },
            body: JSON.stringify(settings.value),
        });
        if (!response.ok) {
            throw new Error('Failed to save settings');
        }
        originalSettings.value = { ...settings.value };
        hasChanges.value = false;
        toast.success('Settings saved successfully');
    } catch (error) {
        console.error('Error saving settings:', error);
        toast.error('Failed to save settings: ' + error.message);
    } finally {
        isLoading.value = false;
    }
};

// Reset settings to last saved state
const resetSettings = () => {
    if (!originalSettings.value) return;
    settings.value = { ...originalSettings.value };
    hasChanges.value = false;
};

// Initialize
onMounted(() => {
    fetchSettings();
});
</script>

<style scoped>
.mc-settings {
    @apply p-6;
}

.form-control {
    @apply mb-4;
}

.label-text {
    @apply font-medium;
}

.card {
    @apply transition-all duration-200;
}

.card:hover {
    @apply shadow-2xl;
}
</style>
