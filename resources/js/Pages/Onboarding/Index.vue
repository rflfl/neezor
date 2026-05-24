<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    currentStep: {
        type: Number,
        default: 1,
    },
    professionals: {
        type: Array,
        default: () => [],
    },
});

const stepComponents = {
    1: () => import('./Step1.vue'),
    2: () => import('./Step2.vue'),
    3: () => import('./Step3.vue'),
};

onMounted(() => {
    const step = parseInt(new URLSearchParams(window.location.search).get('step') || '1');
    if (step >= 1 && step <= 3) {
        router.visit(`/onboarding/step/${step}`);
    } else {
        router.visit('/onboarding/step/1');
    }
});
</script>

<template>
    <Head title="Setup Wizard" />
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Neezor Setup</h1>
                <p class="text-gray-500 mt-2">Let's get your salon configured</p>
            </div>
            <div class="bg-white rounded-lg shadow-xl p-6">
                <p class="text-center text-gray-600">Loading...</p>
            </div>
        </div>
    </div>
</template>