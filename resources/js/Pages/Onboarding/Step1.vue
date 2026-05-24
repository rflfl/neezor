<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    professionals: {
        type: Array,
        default: () => [],
    },
});

const form = ref({
    professionals: [
        {
            name: '',
            email: '',
            phone: '',
            commission_rate: 40,
        },
    ],
});

const addProfessional = () => {
    form.value.professionals.push({
        name: '',
        email: '',
        phone: '',
        commission_rate: 40,
    });
};

const removeProfessional = (index) => {
    if (form.value.professionals.length > 1) {
        form.value.professionals.splice(index, 1);
    }
};

const submit = () => {
    router.post('/dashboard/professionals/bulk', {
        professionals: form.value.professionals
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/onboarding/step/2');
        },
    });
};
</script>

<template>
    <AppLayout title="Onboarding - Professionals">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Setup: Add Professionals
            </h2>
            <p class="text-sm text-gray-500 mt-1">Step 1 of 3</p>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Progress</span>
                            <span class="text-sm text-gray-500">33%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: 33%"></div>
                        </div>
                    </div>

                    <h3 class="text-lg font-medium mb-4">Who works at your salon?</h3>
                    <p class="text-gray-500 text-sm mb-6">Add your professionals. You can set their commission rate here.</p>

                    <div class="space-y-4">
                        <div
                            v-for="(prof, index) in form.professionals"
                            :key="index"
                            class="border border-gray-200 rounded-lg p-4 space-y-3"
                        >
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Professional {{ index + 1 }}</span>
                                <button
                                    v-if="form.professionals.length > 1"
                                    type="button"
                                    @click="removeProfessional(index)"
                                    class="text-red-500 hover:text-red-700 text-sm"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input
                                        v-model="prof.name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. Ana Silva"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input
                                        v-model="prof.email"
                                        type="email"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. ana@example.com"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input
                                        v-model="prof.phone"
                                        type="text"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="e.g. 11999999999"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%)</label>
                                    <input
                                        v-model.number="prof.commission_rate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="addProfessional"
                        class="mt-4 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        + Add another professional
                    </button>

                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            @click="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition"
                        >
                            Next: Add Services
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>