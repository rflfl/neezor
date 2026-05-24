<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    professional: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AppLayout title="Professional Details">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Professional Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">{{ professional.name }}</h3>
                    <p class="text-gray-600">Professional details coming soon...</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>