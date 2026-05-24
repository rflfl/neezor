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
    service: {
        type: Object,
        required: true,
    },
});

const professionals = ref([]);
const loading = ref(true);
const selectedProfessional = ref(null);

onMounted(() => {
    fetchProfessionals();
});

const fetchProfessionals = () => {
    loading.value = true;
    const url = new URL(route('booking.professionals', props.tenant.slug), window.location.origin);
    url.searchParams.set('service_id', props.service.id);
    url.searchParams.set('token', new URLSearchParams(window.location.search).get('token'));

    fetch(url)
        .then(res => res.json())
        .then(data => {
            professionals.value = data.professionals;
            loading.value = false;
        })
        .catch(() => {
            loading.value = false;
        });
};

const selectProfessional = (professional) => {
    selectedProfessional.value = professional.id;
};

const proceedToTimeSlots = () => {
    const url = new URL(route('booking.slots', props.tenant.slug), window.location.origin);
    url.searchParams.set('service_id', props.service.id);
    if (selectedProfessional.value) {
        url.searchParams.set('professional_id', selectedProfessional.value);
    }
    url.searchParams.set('token', new URLSearchParams(window.location.search).get('token'));

    router.visit(url.pathname + url.search, {
        data: {
            service_id: props.service.id,
            professional_id: selectedProfessional.value || null,
        },
        method: 'get',
        preserveState: false,
    });
};

const goBack = () => {
    router.visit(route('booking.index', props.tenant.slug) + window.location.search);
};
</script>

<template>
    <BookingLayout :title="`Agendar - ${tenant.name}`">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <ApplicationMark class="w-12 h-12" />
            </div>
            <p class="text-sm text-indigo-600 font-medium">{{ service.name }}</p>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Escolha um profissional</h1>
            <p class="text-gray-500 text-sm mt-1">Selecione quem ira atende-lo</p>
        </div>

        <button @click="goBack" class="flex items-center text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Voltar
        </button>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else class="space-y-3">
            <button
                @click="selectProfessional({ id: null, name: 'Melhor disponibilidade' })"
                class="w-full text-left bg-white rounded-lg shadow hover:shadow-md transition p-4 flex items-center justify-between"
                :class="{ 'ring-2 ring-indigo-500': selectedProfessional === null }"
                data-testid="professional-best"
            >
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">Melhor disponibilidade</h3>
                        <p class="text-sm text-gray-500">Escolheremos o melhor horario</p>
                    </div>
                </div>
                <div v-if="selectedProfessional === null" class="text-indigo-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>

            <button
                v-for="prof in professionals"
                :key="prof.id"
                @click="selectProfessional(prof)"
                class="w-full text-left bg-white rounded-lg shadow hover:shadow-md transition p-4 flex items-center justify-between"
                :class="{ 'ring-2 ring-indigo-500': selectedProfessional === prof.id }"
                :data-testid="'professional-' + prof.id"
            >
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                        <span class="text-gray-600 font-medium">{{ prof.name.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-900">{{ prof.name }}</h3>
                    </div>
                </div>
                <div v-if="selectedProfessional === prof.id" class="text-indigo-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>

            <div class="pt-6">
                <button
                    @click="proceedToTimeSlots"
                    class="w-full bg-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:bg-indigo-700 transition"
                    data-testid="continue-button"
                >
                    Continuar
                </button>
            </div>
        </div>
    </BookingLayout>
</template>