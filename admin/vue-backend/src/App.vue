<?php
<template>
  <div class="mc-admin">
    <!-- Header -->
    <header class="bg-base-100 p-6 mb-6 rounded-lg shadow-lg">
      <h1 class="text-2xl font-bold flex items-center">
        {{ __('Mesmeric Commerce') }}
        <span class="text-sm font-normal ml-2">v{{ version }}</span>
      </h1>
    </header>

    <!-- Navigation -->
    <nav class="tabs tabs-boxed mb-6">
      <router-link
        v-for="route in routes"
        :key="route.path"
        :to="route.path"
        class="tab"
        :class="{ 'tab-active': isActiveRoute(route.path) }"
      >
        {{ route.name }}
      </router-link>
    </nav>

    <!-- Main Content -->
    <router-view v-slot="{ Component }">
      <transition name="fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>

    <!-- Toast Notifications -->
    <div class="toast toast-end">
      <div v-for="toast in toasts"
           :key="toast.id"
           :class="['alert', `alert-${toast.type}`]"
           @click="dismissToast(toast.id)">
        <span>{{ toast.message }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/MC_useToast';

const { t: __ } = useI18n();
const route = useRoute();
const router = useRouter();
const { toasts, addToast, dismissToast } = useToast();

// Plugin version from WordPress
const version = window.mcAdminData?.version || '1.0.0';

// Available routes
const routes = [
    { path: '/', name: __('Dashboard') },
    { path: '/modules', name: __('Modules') },
    { path: '/settings', name: __('Settings') },
    { path: '/logs', name: __('Logs') },
];

// Active route helper
const isActiveRoute = (path) => route.path === path;

// Watch for WordPress admin notices and convert to toasts
const observeAdminNotices = () => {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.classList?.contains('notice')) {
                    const type = node.classList.contains('notice-success')
                        ? 'success'
                        : node.classList.contains('notice-error')
                        ? 'error'
                        : node.classList.contains('notice-warning')
                        ? 'warning'
                        : 'info';
                    const message = node.textContent.trim();
                    addToast({ message, type });
                    node.remove();
                }
            });
        });
    });

    observer.observe(document.querySelector('#wpbody-content'), {
        childList: true,
        subtree: true,
    });
};

// Initialize
observeAdminNotices();
</script>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.mc-admin {
    @apply p-4 relative min-h-screen;
}
</style>
