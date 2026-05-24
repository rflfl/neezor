<script setup>
defineProps({
    appointment: {
        type: Object,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const statusColors = {
    scheduled: 'bg-blue-100 text-blue-800 border-blue-200',
    confirmed: 'bg-green-100 text-green-800 border-green-200',
    in_progress: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    completed: 'bg-gray-100 text-gray-800 border-gray-200',
    cancelled: 'bg-red-100 text-red-800 border-red-200',
    no_show: 'bg-orange-100 text-orange-800 border-orange-200',
};

const statusLabels = {
    scheduled: 'Agendado',
    confirmed: 'Confirmado',
    in_progress: 'Em atendimento',
    completed: 'Concluído',
    cancelled: 'Cancelado',
    no_show: 'Não compareceu',
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
};

const formatPrice = (cents) => {
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const getStatusClass = (status) => {
    return statusColors[status] || statusColors.scheduled;
};
</script>

<template>
    <div
        :class="[
            'rounded-lg border p-3 cursor-pointer transition-all hover:shadow-md',
            getStatusClass(appointment.status),
            compact ? 'text-xs' : 'text-sm'
        ]"
    >
        <div class="flex items-center justify-between mb-1">
            <span class="font-medium">{{ appointment.client?.name || 'Cliente' }}</span>
            <span
                :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    getStatusClass(appointment.status)
                ]"
            >
                {{ statusLabels[appointment.status] || appointment.status }}
            </span>
        </div>

        <div class="text-gray-600 space-y-0.5">
            <div class="flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ formatTime(appointment.start_at) }}</span>
                <template v-if="appointment.end_at">
                    - <span>{{ formatTime(appointment.end_at) }}</span>
                </template>
            </div>

            <div class="flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
                <span>{{ appointment.service?.name || 'Serviço' }}</span>
            </div>

            <div v-if="!compact" class="flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>{{ appointment.professional?.name || 'Profissional' }}</span>
            </div>

            <div v-if="appointment.price" class="flex items-center gap-1 font-medium text-gray-800">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ formatPrice(appointment.price) }}</span>
            </div>
        </div>
    </div>
</template>