<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    services: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AppLayout title="Services">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Services
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">Service Catalog</h3>
                    <p class="text-gray-600">Service management coming soon...</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>