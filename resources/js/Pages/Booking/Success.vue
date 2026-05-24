<script setup>
import { Head } from '@inertiajs/vue3';
import BookingLayout from '@/Layouts/BookingLayout.vue';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { computed } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    appointment: {
        type: Object,
        default: null,
    },
});

const formatDateTime = (isoStr) => {
    if (!isoStr) return '';
    const date = new Date(isoStr);
    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }) + ' as ' + date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <BookingLayout :title="`Agendamento Confirmado - ${tenant.name}`">
        <div class="text-center">
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Agendamento Confirmado!</h1>
            <p class="text-gray-600 mb-8">O seu agendamento foi realizado com sucesso.</p>

            <div v-if="appointment" class="bg-white rounded-lg shadow p-6 text-left mb-8">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Detalhes</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Servico</span>
                        <span class="font-medium text-gray-900">{{ appointment.service?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data/Hora</span>
                        <span class="text-gray-900">{{ formatDateTime(appointment.start_at) }}</span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <a
                    :href="'/' + tenant.slug + '?token=' + new URLSearchParams(window.location.search).get('token')"
                    class="block w-full bg-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:bg-indigo-700 transition text-center"
                >
                    Fazer novo agendamento
                </a>
            </div>
        </div>
    </BookingLayout>
</template>