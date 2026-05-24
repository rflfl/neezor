<script setup>
import { Head, router } from '@inertiajs/vue3';
import BookingLayout from '@/Layouts/BookingLayout.vue';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
});

const serviceId = ref(null);
const professionalId = ref(null);
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const slots = ref([]);
const loading = ref(true);
const selectedSlot = ref(null);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    serviceId.value = params.get('service_id');
    professionalId.value = params.get('professional_id');
    fetchSlots();
});

const fetchSlots = () => {
    if (!serviceId.value) {
        router.visit(route('booking.index', props.tenant.slug) + window.location.search);
        return;
    }
    loading.value = true;
    const url = new URL(route('booking.slots', props.tenant.slug), window.location.origin);
    url.searchParams.set('service_id', serviceId.value);
    if (professionalId.value) {
        url.searchParams.set('professional_id', professionalId.value);
    }
    url.searchParams.set('date', selectedDate.value);
    url.searchParams.set('token', new URLSearchParams(window.location.search).get('token'));

    fetch(url)
        .then(res => res.json())
        .then(data => {
            slots.value = data.slots;
            loading.value = false;
        })
        .catch(() => {
            loading.value = false;
        });
};

watch(selectedDate, () => {
    fetchSlots();
});

const selectSlot = (slot) => {
    selectedSlot.value = slot;
};

const goBack = () => {
    router.visit(route('booking.professional', props.tenant.slug) + '?service_id=' + serviceId.value + '&' + window.location.search.split('&').filter(p => p.startsWith('token=')).join('&'));
};

const proceedToConfirm = () => {
    if (!selectedSlot.value) return;
    const url = new URL(route('booking.confirm', props.tenant.slug), window.location.origin);
    url.searchParams.set('service_id', serviceId.value);
    if (professionalId.value) {
        url.searchParams.set('professional_id', professionalId.value);
    }
    url.searchParams.set('start_at', selectedSlot.value.start);
    url.searchParams.set('end_at', selectedSlot.value.end);
    url.searchParams.set('token', new URLSearchParams(window.location.search).get('token'));

    router.visit(url.pathname + url.search, {
        method: 'get',
        preserveState: false,
    });
};

const changeDate = (days) => {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() + days);
    selectedDate.value = date.toISOString().split('T')[0];
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

const formatTime = (isoStr) => {
    const date = new Date(isoStr);
    return date.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const canGoBack = () => {
    const today = new Date().toISOString().split('T')[0];
    return selectedDate.value > today;
};
</script>

<template>
    <BookingLayout :title="`Agendar - ${tenant.name}`">
        <div class="text-center mb-6">
            <div class="flex justify-center mb-4">
                <ApplicationMark class="w-12 h-12" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Escolha um horario</h1>
        </div>

        <button @click="goBack" class="flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Voltar
        </button>

        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex items-center justify-between">
                <button
                    @click="changeDate(-1)"
                    :disabled="!canGoBack()"
                    class="p-2 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="font-medium text-gray-900">{{ formatDate(selectedDate) }}</span>
                <button
                    @click="changeDate(1)"
                    class="p-2 rounded-md hover:bg-gray-100"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="slots.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-500">Nenhum horario disponivel neste dia.</p>
            <button
                @click="changeDate(1)"
                class="mt-4 text-indigo-600 hover:text-indigo-700 font-medium"
            >
                Ver proximo dia
            </button>
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <button
                v-for="(slot, index) in slots"
                :key="index"
                @click="selectSlot(slot)"
                class="py-3 px-4 rounded-lg text-center font-medium transition"
                :class="selectedSlot === slot
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50'"
                :data-testid="'slot-' + index"
            >
                {{ formatTime(slot.start) }}
            </button>
        </div>

        <div v-if="slots.length > 0" class="pt-6">
            <button
                @click="proceedToConfirm"
                :disabled="!selectedSlot"
                class="w-full bg-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                data-testid="continue-button"
            >
                Continuar
            </button>
        </div>
    </BookingLayout>
</template>