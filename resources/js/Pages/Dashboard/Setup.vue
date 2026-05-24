<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
});

onMounted(() => {
    router.visit('/onboarding/step/1');
});
</script>

<template>
    <Head title="Setup Required" />
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <div class="text-center">
                <div class="text-6xl mb-4">🚀</div>
                <h1 class="text-3xl font-bold text-gray-900">Welcome to Neezor!</h1>
                <p class="text-gray-500 mt-4 mb-8">Let's set up your salon in a few quick steps.</p>
                <button
                    @click="$inertia.visit('/onboarding/step/1')"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-base text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition"
                >
                    Start Setup
                </button>
            </div>
        </div>
    </div>
</template>