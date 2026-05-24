<script setup>
import { Head, router } from '@inertiajs/vue3';
import BookingLayout from '@/Layouts/BookingLayout.vue';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { ref } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
    service: {
        type: Object,
        required: true,
    },
    professional: {
        type: Object,
        default: null,
    },
    slot: {
        type: Object,
        required: true,
    },
});

const clientName = ref('');
const clientPhone = ref('');
const submitting = ref(false);
const error = ref(null);

const formatPrice = (cents) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
};

const formatDateTime = (isoStr) => {
    const date = new Date(isoStr);
    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    }) + ' as ' + date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (minutes) => {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
};

const goBack = () => {
    const params = new URLSearchParams(window.location.search);
    router.visit(route('booking.slots', props.tenant.slug) + '?' + params.toString());
};

const submitBooking = async () => {
    if (!clientName.value.trim() || !clientPhone.value.trim()) {
        error.value = 'Preencha seu nome e telefone.';
        return;
    }

    submitting.value = true;
    error.value = null;

    const params = new URLSearchParams(window.location.search);
    const formData = {
        professional_id: props.professional?.id || 0,
        service_id: props.service.id,
        start_at: props.slot.start_at,
        end_at: props.slot.end_at,
        client_name: clientName.value.trim(),
        client_phone: clientPhone.value.trim(),
    };

    try {
        const response = await fetch(
            route('booking.appointments.store', props.tenant.slug) + '?' + params.toString(),
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(formData),
            }
        );

        const data = await response.json();

        if (data.success) {
            const params = new URLSearchParams(window.location.search);
            params.set('service_name', props.service.name);
            params.set('start_at', props.slot.start_at);
            router.visit(route('booking.success', props.tenant.slug) + '?' + params.toString());
        } else {
            error.value = data.error || 'Erro ao confirmar agendamento.';
            submitting.value = false;
        }
    } catch (e) {
        error.value = 'Erro ao conectar. Tente novamente.';
        submitting.value = false;
    }
};
</script>

<template>
    <BookingLayout :title="`Confirmar - ${tenant.name}`">
        <div class="text-center mb-6">
            <div class="flex justify-center mb-4">
                <ApplicationMark class="w-12 h-12" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Confirme seu agendamento</h1>
        </div>

        <button @click="goBack" class="flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Voltar
        </button>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Resumo do agendamento</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Servico</span>
                    <span class="font-medium text-gray-900">{{ service.name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Duracao</span>
                    <span class="text-gray-900">{{ formatDuration(service.duration_minutes) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Profissional</span>
                    <span class="text-gray-900">{{ professional?.name || 'A definir' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Data/Hora</span>
                    <span class="text-gray-900">{{ formatDateTime(slot.start_at) }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between">
                    <span class="text-gray-600">Valor</span>
                    <span class="font-semibold text-indigo-600">{{ formatPrice(service.price) }}</span>
                </div>
            </div>
        </div>

        <form @submit.prevent="submitBooking" class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Seus dados</h2>

            <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ error }}
            </div>

            <div class="space-y-4">
                <div>
                    <label for="client_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Seu nome *
                    </label>
                    <input
                        id="client_name"
                        v-model="clientName"
                        type="text"
                        required
                        placeholder="Ex: Maria Silva"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        data-testid="client-name-input"
                    />
                </div>

                <div>
                    <label for="client_phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Telefone (WhatsApp) *
                    </label>
                    <input
                        id="client_phone"
                        v-model="clientPhone"
                        type="tel"
                        required
                        placeholder="(11) 99999-9999"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        data-testid="client-phone-input"
                    />
                </div>
            </div>

            <button
                type="submit"
                :disabled="submitting"
                class="w-full mt-6 bg-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                data-testid="confirm-booking-button"
            >
                <span v-if="submitting">Confirmando...</span>
                <span v-else>Confirmar Agendamento</span>
            </button>

            <p class="text-xs text-gray-500 text-center mt-4">
                Ao confirmar, voce recebera uma mensagem de confirmacao.
            </p>
        </form>
    </BookingLayout>
</template>