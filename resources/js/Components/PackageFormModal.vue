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
    package: {
        type: Object,
        default: null,
    },
    availableServices: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'save']);

const form = ref({
    id: null,
    name: '',
    price: '',
    valid_until_days: 30,
    services: [],
});

const errors = ref({});
const isSubmitting = ref(false);

const isEdit = computed(() => !!props.package?.id);

const modalTitle = computed(() => {
    return isEdit.value ? 'Editar Pacote' : 'Novo Pacote';
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        resetForm();
        if (props.package) {
            populateForm(props.package);
        }
    }
});

const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        price: '',
        valid_until_days: 30,
        services: [],
    };
    errors.value = {};
};

const populateForm = (pkg) => {
    form.value = {
        id: pkg.id,
        name: pkg.name || '',
        price: pkg.price ? (pkg.price / 100).toString() : '',
        valid_until_days: pkg.valid_until_days || 30,
        services: (pkg.services || []).map(s => ({
            service_id: s.id || s.service_id,
            session_count: s.pivot?.session_count || s.session_count || 1,
        })),
    };
};

const addService = () => {
    if (props.availableServices.length === 0) return;
    form.value.services.push({
        service_id: props.availableServices[0].id,
        session_count: 1,
    });
};

const removeService = (index) => {
    form.value.services.splice(index, 1);
};

const getServiceName = (serviceId) => {
    const service = props.availableServices.find(s => s.id === parseInt(serviceId));
    return service ? service.name : 'Serviço desconhecido';
};

const getServicePrice = (serviceId) => {
    const service = props.availableServices.find(s => s.id === parseInt(serviceId));
    if (!service || !service.price) return '-';
    return (service.price / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const validateForm = () => {
    errors.value = {};

    if (!form.value.name || !form.value.name.trim()) {
        errors.value.name = 'Nome é obrigatório';
    }
    if (form.value.price === '' || form.value.price === null) {
        errors.value.price = 'Preço é obrigatório';
    } else if (parseFloat(form.value.price) < 0) {
        errors.value.price = 'Preço não pode ser negativo';
    }
    if (!form.value.valid_until_days || form.value.valid_until_days < 1) {
        errors.value.valid_until_days = 'Validade inválida';
    }
    if (form.value.services.length === 0) {
        errors.value.services = 'Adicione pelo menos um serviço';
    }

    return Object.keys(errors.value).length === 0;
};

const handleSubmit = () => {
    if (!validateForm()) return;

    isSubmitting.value = true;
    const data = {
        name: form.value.name,
        price: Math.round(parseFloat(form.value.price) * 100),
        valid_until_days: parseInt(form.value.valid_until_days),
        services: form.value.services.map(s => ({
            service_id: parseInt(s.service_id),
            session_count: parseInt(s.session_count),
        })),
    };
    if (form.value.id) {
        data.id = form.value.id;
    }
    emit('save', data);
    isSubmitting.value = false;
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" :max-width="'2xl'" @close="handleClose">
        <div class="p-6" data-testid="package-modal">
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
                    <InputLabel for="name" value="Nome do Pacote *" />
                    <TextInput
                        id="name"
                        data-testid="name-input"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        placeholder="Ex: Pacote Bronze"
                    />
                    <InputError v-if="errors.name" :message="errors.name" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="price" value="Preço (R$) *" />
                        <TextInput
                            id="price"
                            type="number"
                            data-testid="price-input"
                            step="0.01"
                            v-model="form.price"
                            class="mt-1 block w-full"
                            placeholder="0.00"
                        />
                        <InputError v-if="errors.price" :message="errors.price" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="validity" value="Validade (dias) *" />
                        <select
                            id="validity"
                            data-testid="validity-input"
                            v-model="form.valid_until_days"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option :value="15">15 dias</option>
                            <option :value="30">30 dias</option>
                            <option :value="60">60 dias</option>
                            <option :value="90">90 dias</option>
                            <option :value="180">180 dias</option>
                            <option :value="365">1 ano</option>
                        </select>
                        <InputError v-if="errors.valid_until_days" :message="errors.valid_until_days" class="mt-2" />
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <InputLabel value="Serviços do Pacote *" />
                        <button
                            type="button"
                            @click="addService"
                            data-testid="add-service-button"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            + Adicionar serviço
                        </button>
                    </div>
                    <InputError v-if="errors.services" :message="errors.services" class="mb-2" />

                    <div v-if="form.services.length === 0" class="text-sm text-gray-500 text-center py-4 border border-dashed border-gray-300 rounded-md">
                        Nenhum serviço adicionado. Clique em "Adicionar serviço" para começar.
                    </div>

                    <div v-for="(serviceItem, index) in form.services" :key="index" class="flex items-center gap-2 mb-2">
                        <select
                            v-model="serviceItem.service_id"
                            class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                        >
                            <option v-for="svc in availableServices" :key="svc.id" :value="svc.id">
                                {{ svc.name }} - {{ getServicePrice(svc.id) }}
                            </option>
                        </select>
                        <div class="flex items-center gap-1">
                            <input
                                type="number"
                                v-model="serviceItem.session_count"
                                min="1"
                                data-testid="session-count-input"
                                class="w-20 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-center"
                            />
                            <span class="text-sm text-gray-500">sessões</span>
                        </div>
                        <button
                            type="button"
                            @click="removeService(index)"
                            class="text-red-600 hover:text-red-800 p-1"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 pt-4 border-t">
                    <PrimaryButton type="submit" data-testid="submit-button" :disabled="isSubmitting">
                        {{ isEdit ? 'Salvar' : 'Criar Pacote' }}
                    </PrimaryButton>
                    <SecondaryButton type="button" @click="handleClose">
                        Cancelar
                    </SecondaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>