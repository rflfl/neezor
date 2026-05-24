<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    professionals: {
        type: Array,
        default: () => [],
    },
});

const form = ref({
    schedules: props.professionals.map(prof => ({
        professional_id: prof.id,
        professional_name: prof.name,
        working_hours: {
            monday: { enabled: true, start: '09:00', end: '18:00' },
            tuesday: { enabled: true, start: '09:00', end: '18:00' },
            wednesday: { enabled: true, start: '09:00', end: '18:00' },
            thursday: { enabled: true, start: '09:00', end: '18:00' },
            friday: { enabled: true, start: '09:00', end: '18:00' },
            saturday: { enabled: false, start: '09:00', end: '14:00' },
            sunday: { enabled: false, start: '09:00', end: '14:00' },
        },
    })),
});

const days = [
    { key: 'monday', label: 'Mon' },
    { key: 'tuesday', label: 'Tue' },
    { key: 'wednesday', label: 'Wed' },
    { key: 'thursday', label: 'Thu' },
    { key: 'friday', label: 'Fri' },
    { key: 'saturday', label: 'Sat' },
    { key: 'sunday', label: 'Sun' },
];

const allDaysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

const toggleAllDays = (scheduleIndex) => {
    const schedule = form.value.schedules[scheduleIndex];
    const allEnabled = allDaysOfWeek.every(d => schedule.working_hours[d].enabled);
    allDaysOfWeek.forEach(d => {
        schedule.working_hours[d].enabled = !allEnabled;
    });
};

const submit = () => {
    router.post('/onboarding/complete', {
        schedules: form.value.schedules.map(s => ({
            professional_id: s.professional_id,
            working_hours: s.working_hours,
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/dashboard');
        },
    });
};
</script>

<template>
    <AppLayout title="Onboarding - Schedule">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Setup: Configure Schedule
            </h2>
            <p class="text-sm text-gray-500 mt-1">Step 3 of 3</p>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="mb-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Progress</span>
                            <span class="text-sm text-gray-500">100%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>

                    <h3 class="text-lg font-medium mb-4">Set default working hours</h3>
                    <p class="text-gray-500 text-sm mb-6">Configure the default schedule for each professional. You can adjust these later.</p>

                    <div class="space-y-6">
                        <div
                            v-for="(schedule, schedIndex) in form.schedules"
                            :key="schedule.professional_id"
                            class="border border-gray-200 rounded-lg p-4"
                        >
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-800">{{ schedule.professional_name }}</h4>
                                <button
                                    type="button"
                                    @click="toggleAllDays(schedIndex)"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    Toggle all
                                </button>
                            </div>

                            <div class="grid grid-cols-7 gap-2">
                                <div
                                    v-for="day in days"
                                    :key="day.key"
                                    class="text-center"
                                >
                                    <label class="text-xs font-medium text-gray-500 mb-1 block">{{ day.label }}</label>
                                    <input
                                        type="checkbox"
                                        v-model="schedule.working_hours[day.key].enabled"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mb-1"
                                    />
                                    <div v-if="schedule.working_hours[day.key].enabled" class="space-y-1">
                                        <input
                                            type="time"
                                            v-model="schedule.working_hours[day.key].start"
                                            class="w-full text-xs rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-xs text-gray-400">to</span>
                                        <input
                                            type="time"
                                            v-model="schedule.working_hours[day.key].end"
                                            class="w-full text-xs rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                    </div>
                                    <span v-else class="text-xs text-gray-400">Off</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button
                            type="button"
                            @click="router.visit('/onboarding/step/2')"
                            class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition"
                        >
                            Back
                        </button>
                        <button
                            type="button"
                            @click="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition"
                        >
                            Complete Setup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>