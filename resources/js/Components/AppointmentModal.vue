<script setup>
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    appointment: {
        type: Object,
        default: null,
    },
    clients: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    professionals: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'save', 'cancel', 'complete', 'delete']);

const form = ref({
    id: null,
    client_id: '',
    professional_id: '',
    service_id: '',
    date: '',
    time: '',
    price: '',
    status: 'scheduled',
});

const errors = ref({});
const isSubmitting = ref(false);

const isEdit = computed(() => !!props.appointment?.id);

const modalTitle = computed(() => {
    if (isEdit.value) {
        return 'Editar Agendamento';
    }
    return 'Novo Agendamento';
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
        if (props.appointment) {
            populateForm(props.appointment);
        }
    }
});

watch(() => props.appointment, (newVal) => {
    if (newVal && props.show) {
        populateForm(newVal);
    }
});

const resetForm = () => {
    form.value = {
        id: null,
        client_id: '',
        professional_id: '',
        service_id: '',
        date: new Date().toISOString().split('T')[0],
        time: '09:00',
        price: '',
        status: 'scheduled',
    };
    errors.value = {};
};

const populateForm = (appointment) => {
    const startDate = new Date(appointment.start_at);
    form.value = {
        id: appointment.id,
        client_id: appointment.client_id || '',
        professional_id: appointment.professional_id || '',
        service_id: appointment.service_id || '',
        date: startDate.toISOString().split('T')[0],
        time: startDate.toTimeString().slice(0, 5),
        price: appointment.price ? (appointment.price / 100).toString() : '',
        status: appointment.status || 'scheduled',
    };
};

const selectedService = computed(() => {
    return props.services.find(s => s.id === form.value.service_id);
});

watch(() => form.value.service_id, (serviceId) => {
    if (serviceId && !isEdit.value) {
        const service = props.services.find(s => s.id === parseInt(serviceId));
        if (service?.price) {
            form.value.price = (service.price / 100).toString();
        }
    }
});

const validateForm = () => {
    errors.value = {};

    if (!form.value.client_id) {
        errors.value.client_id = 'Selecione um cliente';
    }
    if (!form.value.professional_id) {
        errors.value.professional_id = 'Selecione um profissional';
    }
    if (!form.value.service_id) {
        errors.value.service_id = 'Selecione um serviço';
    }
    if (!form.value.date) {
        errors.value.date = 'Selecione uma data';
    }
    if (!form.value.time) {
        errors.value.time = 'Selecione um horário';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) return;

    isSubmitting.value = true;

    const startAt = `${form.value.date}T${form.value.time}:00`;
    const service = selectedService.value;
    const durationMinutes = service?.duration_minutes || 60;
    const endDate = new Date(new Date(startAt).getTime() + durationMinutes * 60000);
    const endAt = endDate.toISOString().slice(0, 16);

    const data = {
        client_id: parseInt(form.value.client_id),
        professional_id: parseInt(form.value.professional_id),
        service_id: parseInt(form.value.service_id),
        start_at: startAt,
        end_at: endAt,
        price: form.value.price ? Math.round(parseFloat(form.value.price) * 100) : null,
        status: form.value.status,
    };

    if (isEdit.value) {
        data.id = form.value.id;
    }

    emit('save', data);
    isSubmitting.value = false;
};

const handleClose = () => {
    emit('close');
};

const handleCancel = () => {
    emit('cancel', form.value.id);
};

const handleComplete = () => {
    emit('complete', form.value.id);
};

const handleDelete = () => {
    emit('delete', form.value.id);
};

const statusOptions = [
    { value: 'scheduled', label: 'Agendado', color: 'bg-blue-100 text-blue-800' },
    { value: 'confirmed', label: 'Confirmado', color: 'bg-green-100 text-green-800' },
    { value: 'in_progress', label: 'Em atendimento', color: 'bg-yellow-100 text-yellow-800' },
    { value: 'completed', label: 'Concluído', color: 'bg-gray-100 text-gray-800' },
    { value: 'cancelled', label: 'Cancelado', color: 'bg-red-100 text-red-800' },
    { value: 'no_show', label: 'Não compareceu', color: 'bg-orange-100 text-orange-800' },
];

const formatPrice = (cents) => {
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};
</script>

<template>
    <Modal :show="show" :max-width="'lg'" @close="handleClose">
        <div class="p-6" data-testid="appointment-modal">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">{{ modalTitle }}</h3>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600"
                    @click="handleClose"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <InputLabel for="client" value="Cliente" />
                    <select
                        id="client"
                        data-testid="client-select"
                        v-model="form.client_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Selecione um cliente</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </option>
                    </select>
                    <InputError v-if="errors.client_id" :message="errors.client_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="professional" value="Profissional" />
                    <select
                        id="professional"
                        data-testid="professional-select"
                        v-model="form.professional_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Selecione um profissional</option>
                        <option v-for="professional in professionals" :key="professional.id" :value="professional.id">
                            {{ professional.name }}
                        </option>
                    </select>
                    <InputError v-if="errors.professional_id" :message="errors.professional_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="service" value="Serviço" />
                    <select
                        id="service"
                        data-testid="service-select"
                        v-model="form.service_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="">Selecione um serviço</option>
                        <option v-for="service in services" :key="service.id" :value="service.id">
                            {{ service.name }} - {{ formatPrice(service.price) }} ({{ service.duration_minutes }}min)
                        </option>
                    </select>
                    <InputError v-if="errors.service_id" :message="errors.service_id" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="date" value="Data" />
                        <input
                            id="date"
                            type="date"
                            data-testid="date-input"
                            v-model="form.date"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        />
                        <InputError v-if="errors.date" :message="errors.date" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="time" value="Horário" />
                        <input
                            id="time"
                            type="time"
                            data-testid="time-input"
                            v-model="form.time"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        />
                        <InputError v-if="errors.time" :message="errors.time" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="price" value="Preço (R$)" />
                    <TextInput
                        id="price"
                        type="number"
                        data-testid="price-input"
                        step="0.01"
                        v-model="form.price"
                        placeholder="0.00"
                        class="mt-1 block w-full"
                    />
                </div>

                <div v-if="isEdit">
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    <PrimaryButton type="submit" data-testid="submit-button" :disabled="isSubmitting">
                        {{ isEdit ? 'Salvar' : 'Criar Agendamento' }}
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="handleClose">
                        Cancelar
                    </SecondaryButton>
                </div>
            </form>

            <div v-if="isEdit" class="mt-6 pt-4 border-t space-y-2">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="form.status !== 'completed'"
                        type="button"
                        data-testid="complete-button"
                        @click="handleComplete"
                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-sm rounded-md hover:bg-green-700"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Marcar como Concluído
                    </button>
                    <button
                        v-if="form.status !== 'cancelled'"
                        type="button"
                        data-testid="cancel-button"
                        @click="handleCancel"
                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm rounded-md hover:bg-red-700"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancelar
                    </button>
                </div>
                <button
                    type="button"
                    @click="handleDelete"
                    class="text-sm text-red-600 hover:text-red-800 underline"
                >
                    Excluir agendamento
                </button>
            </div>
        </div>
    </Modal>
</template>