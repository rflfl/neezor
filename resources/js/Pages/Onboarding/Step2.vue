<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    professionals: {
        type: Array,
        default: () => [],
    },
});

const form = ref({
    services: [
        {
            name: '',
            duration_minutes: 60,
            price: 5000,
        },
    ],
});

const addService = () => {
    form.value.services.push({
        name: '',
        duration_minutes: 60,
        price: 5000,
    });
};

const removeService = (index) => {
    if (form.value.services.length > 1) {
        form.value.services.splice(index, 1);
    }
};

const formatPrice = (value) => {
    return (value / 100).toFixed(2).replace('.', ',');
};

const parsePrice = (formatted) => {
    return Math.round(parseFloat(formatted.replace(',', '.')) * 100);
};

const submit = () => {
    router.post('/dashboard/services/bulk', {
        services: form.value.services
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/onboarding/step/3');
        },
    });
};
</script>

<template>
    <AppLayout title="Onboarding - Services">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Setup: Add Services
            </h2>
            <p class="text-sm text-gray-500 mt-1">Step 2 of 3</p>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Progress</span>
                            <span class="text-sm text-gray-500">66%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: 66%"></div>
                        </div>
                    </div>

                    <h3 class="text-lg font-medium mb-4">What services do you offer?</h3>
                    <p class="text-gray-500 text-sm mb-6">Add your services with duration and price. Prices are in centavos (R$50,00 = 5000).</p>

                    <div class="space-y-4">
                        <div
                            v-for="(svc, index) in form.services"
                            :key="index"
                            class="border border-gray-200 rounded-lg p-4 space-y-3"
                        >
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Service {{ index + 1 }}</span>
                                <button
                                    v-if="form.services.length > 1"
                                    type="button"
                                    @click="removeService(index)"
                                    class="text-red-500 hover:text-red-700 text-sm"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input
                                        v-model="svc.name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. Corte Feminino"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (min)</label>
                                    <select
                                        v-model.number="svc.duration_minutes"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option :value="15">15 min</option>
                                        <option :value="30">30 min</option>
                                        <option :value="45">45 min</option>
                                        <option :value="60">60 min</option>
                                        <option :value="90">90 min</option>
                                        <option :value="120">120 min</option>
                                        <option :value="180">180 min</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (R$)</label>
                                    <input
                                        :value="formatPrice(svc.price)"
                                        @input="svc.price = parsePrice($event.target.value)"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="50,00"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="addService"
                        class="mt-4 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        + Add another service
                    </button>

                    <div class="mt-6 flex justify-between">
                        <button
                            type="button"
                            @click="router.visit('/onboarding/step/1')"
                            class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition"
                        >
                            Back
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition"
                        >
                            Next: Configure Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>