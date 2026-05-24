<script setup>
import { Head, router } from '@inertiajs/vue3';
import BookingLayout from '@/Layouts/BookingLayout.vue';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    tenant: {
        type: Object,
        required: true,
    },
});

const services = ref([]);
const loading = ref(true);

onMounted(() => {
    fetchServices();
});

const fetchServices = () => {
    loading.value = true;
    fetch(route('booking.services', props.tenant.slug) + window.location.search)
        .then(res => res.json())
        .then(data => {
            services.value = data.services;
            loading.value = false;
        })
        .catch(() => {
            loading.value = false;
        });
};

const selectService = (service) => {
    router.visit(route('booking.professional', props.tenant.slug) + window.location.search, {
        data: { service_id: service.id },
        method: 'get',
    });
};

const formatPrice = (cents) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
};

const formatDuration = (minutes) => {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`;
};
</script>

<template>
    <BookingLayout :title="`Agende online - ${tenant.name}`">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <ApplicationMark class="w-12 h-12" />
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ tenant.name }}</h1>
            <p class="text-gray-600 mt-2">Selecione um serviço para agendar</p>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="services.length === 0" class="text-center py-12">
            <p class="text-gray-500">Nenhum servico disponivel no momento.</p>
        </div>

        <div v-else class="space-y-3">
            <button
                v-for="service in services"
                :key="service.id"
                @click="selectService(service)"
                class="w-full text-left bg-white rounded-lg shadow hover:shadow-md transition p-4 flex items-center justify-between"
                :data-testid="'service-' + service.id"
            >
                <div>
                    <h3 class="font-medium text-gray-900">{{ service.name }}</h3>
                    <p class="text-sm text-gray-500">{{ formatDuration(service.duration_minutes) }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-indigo-600">{{ formatPrice(service.price) }}</p>
                </div>
            </button>
        </div>
    </BookingLayout>
</template>